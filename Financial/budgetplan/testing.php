<?php

$groqApiKey = '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';
$groqEndpoint = 'https://api.groq.com/openai/v1/chat/completions';

include_once '../utility/connection.php';


$stmtCashOnHand = $pdo->query("
    SELECT IFNULL(SUM(jd.debit) - SUM(jd.credit), 0) AS cashOnHand
    FROM ledger.details jd
    JOIN ledger.chartofaccount c ON jd.accountID = c.accountID
    WHERE c.accountName = 'Cash on Hand' 
");
$cashOnHand = $stmtCashOnHand->fetchColumn() ?: 0;

$stmtBankBalance = $pdo->query("
    SELECT IFNULL(SUM(f.Amount - f.UsedAmount - COALESCE(f.Transfer, 0)), 0) AS bankBalance
    FROM ledger.funds f
    WHERE f.Archive = 'NO'
");
$bankBalance = $stmtBankBalance->fetchColumn() ?: 0;

$totalGLCash = $cashOnHand + $bankBalance;


$currentYear = date('Y');           
$targetYear = $currentYear + 1;     

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'forecast') {
        // Fetch up to 5 years historical data
        $minYear = $currentYear - 5;
        $stmt = $pdo->prepare("
            SELECT Name, Amount, UsedBudget, status, approval, DateValid AS year
            FROM budget.departmentbudget
            WHERE DateValid BETWEEN :minYear AND :lastYear
              AND Archive = 'NO'
              AND status != 'Cancel'
              
            ORDER BY Name, DateValid ASC
        ");
        $stmt->execute(['minYear' => $minYear, 'lastYear' => $currentYear - 1]);
        $historicalData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by department
        $deptHistory = [];
        foreach ($historicalData as $row) {
            $dept = $row['Name'];
            if (!isset($deptHistory[$dept])) $deptHistory[$dept] = [];
            $deptHistory[$dept][] = [
                'year' => (int)$row['year'],
                'amount' => (int)$row['Amount'],
                'used' => (int)$row['UsedBudget']
            ];
        }

        // Simple baseline forecasts (for fallback)
        $simpleForecasts = [];
        foreach ($deptHistory as $dept => $history) {
            $last = end($history);
            $prevAmount = $last['amount'] ?? 0;
            $used = $last['used'] ?? 0;
            $utilization = $prevAmount > 0 ? $used / $prevAmount : 0;

            $growth = 0.10; // ~10% for 2027 (inflation + education growth)
            if ($utilization > 0.95) $growth += 0.12;
            if ($utilization < 0.40) $growth -= 0.05;

            $forecastAmount = round($prevAmount * (1 + $growth));

            $simpleForecasts[] = [
                'department' => $dept,
                'history' => $history,
                'simple_forecast' => $forecastAmount,
                'suggested_growth' => round($growth * 100, 1) . '%'
            ];
        }

        echo json_encode([
            'status' => 'success',
            'dept_history' => $deptHistory,
            'simple_forecasts' => $simpleForecasts,
            'total_cash' => $totalGLCash
        ]);
        exit;
    }

 
    if ($action === 'get_groq') {
        $baselinesJson = $_POST['baselines'] ?? '[]';
        $baselines = json_decode($baselinesJson, true);

     $prompt = <<<PROMPT
You are an expert financial advisor specializing in freight management systems and logistics budgeting in the Philippines.

Current context: Early 2026 (post-Dec 2025 data release)
Current available cash/funds (Cash on Hand + Bank Balance): ₱{$totalGLCash}

Historical data (multi-year per department/division in the freight management system): [available in context]

Current & forward-looking economic & logistics trends (as of early 2026 with outlook to 2027–2028):
- Inflation: Actual Dec 2025 = 1.8% (driven by food & transport); full-year 2025 average = 1.7% (lowest since 2016)
  - Forecast: Gradual rise to ~3.2% average in 2026, then ~3.0% in 2027 (BSP latest projections, within 2–4% target range)
- GDP growth: Revised DBCC targets = 5–6% in 2026, 5.5–6.5% in 2027 (down from prior due to 2025 slowdown factors, but still solid regional performance)
- Logistics/freight sector: Strong medium-term growth projected (overall market ~USD 15–20B in 2025, expected CAGR 5.8–8% to 2030+)
  - Key drivers: E-commerce surge, domestic trade recovery, government "Build, Better, More" infrastructure push (ports, roads, corridors)
  - Persistent cost pressures: Fuel price volatility (global + local), toll increases, labor costs (rising ~6% annually), port/road congestion, high inter-island shipping expenses
  - Opportunities: Digitalization (freight software, real-time tracking, automation), 3PL partnerships, fleet efficiency gains, and infrastructure improvements reducing long-term costs

Baseline 2027 forecasts (from TensorFlow.js ML or simple rules) for each department/division in the freight management system:
{$baselinesJson}

Recommend realistic, forward-looking budget allocations **for 2027** (with medium-term resilience in mind).
- Apply ~3% inflation buffer + extra for fuel/transport volatility (ongoing risk in PH logistics)
- Prioritize cost drivers: fuel/diesel, vehicle maintenance, driver wages/benefits, tolls/permits, insurance, digital tools/software, warehousing, inter-island shipping, compliance
- Respect current available cash: Total recommended budget should be prudent; flag if significantly exceeding liquid funds and suggest efficiency measures (route optimization, fleet modernization, 3PL outsourcing, fuel hedging)
- Balance growth opportunities (digitalization, e-commerce demand) with cost control for sustainable operations

Return clean JSON only - no extra text, explanations, markdown, or code:

{
  "total_available_cash": {$totalGLCash},
  "recommended_total_2027": number,
  "recommendations": [
    {
      "department": "Exact department/division name",
      "suggested_amount_2027": number,
      "reason": "short explanation (inflation/fuel volatility/logistics trends/cash adjustment)",
      "risk": "low|medium|high"
    }
  ],
  "overall_advice": "short paragraph for 2027 freight management budgeting (include medium-term outlook)",
  "cash_warning": "optional warning if total recommended significantly exceeds available cash"
}
PROMPT;
        $payload = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.65,
            'max_tokens' => 1200,
            'response_format' => ['type' => 'json_object']
        ];

        $ch = curl_init($groqEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $groqApiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        echo json_encode([
            'status' => 'success',
            'groq_ai' => $result['choices'][0]['message']['content'] ?? null,
            'error' => $result['error'] ?? null
        ]);
        exit;
    }
}
?>