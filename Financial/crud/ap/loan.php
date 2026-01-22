<?php
ob_start();
include_once __DIR__ . '/../../utility/connection.php';
require_once __DIR__ . '/../../fpdf186/fpdf.php';
date_default_timezone_set('Asia/Manila');
include_once('../../utility/head.php');


class LoanPDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Loan Agreement', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 5, 'Kasunduan sa Pautang', 0, 1, 'C');
        $this->Cell(0, 5, 'Republic of the Philippines / Republika ng Pilipinas', 0, 1, 'C');
        $this->Ln(5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function ChapterTitle($title) {
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 6, $title, 0, 1, 'L');
        $this->Ln(4);
    }

    function AddLine($text, $italic = false) {
        $this->SetFont('Arial', $italic ? 'I' : '', 10);
        $parts = preg_split('/(__.*?__)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part) {
            if (preg_match('/^__(.*?)__$/', $part, $matches)) {
                $this->SetFont('Arial', 'B' . ($italic ? 'I' : ''), 10);
                $this->Write(5, $matches[1]);
                $this->SetFont('Arial', $italic ? 'I' : '', 10);
            } else {
                $this->Write(5, $part);
            }
        }
        $this->Ln(6);
    }

    function AddBold($text) {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, $text, 0, 1, 'L');
    }

    function AddAlignedBlocks($leftTitle, $rightTitle, $fields) {
        $colWidth = 90;
        $lineHeight = 6;
        $yStart = $this->GetY();
        $this->SetXY(10, $yStart);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($colWidth, $lineHeight, $leftTitle, 0, 2, 'L');
        $this->SetFont('Arial', '', 10);
        foreach ($fields as $field) {
            $this->Cell($colWidth, $lineHeight, $field, 0, 2, 'L');
        }
        $yLeft = $this->GetY();
        $this->SetXY(110, $yStart);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($colWidth, $lineHeight, $rightTitle, 0, 2, 'L');
        $this->SetFont('Arial', '', 10);
        foreach ($fields as $field) {
            $this->Cell($colWidth, $lineHeight, $field, 0, 2, 'L');
        }
        $yRight = $this->GetY();
        $this->SetY(max($yLeft, $yRight) + 10);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $loantitle = $_POST['loanTitle'];
    $vendor = $_POST['vendorId'];
    $penaltyInterest = $_POST['PenaltyRate'];
    $disburse = $_POST['Disbursement'];
    $collateral = $_POST['Collateral'];
    $penalty = $_POST['Penalty'];
    $purpose = $_POST['Purpose'];
    $receive = $_POST['Received'];
    $day = $_POST['day'];
    $installment = $_POST['Installment'];
    $loan = $_POST['loanAmount'];
    $interest = $_POST['interestRate'];

    $loantitle = htmlspecialchars($loantitle, ENT_QUOTES, 'UTF-8');
    $purpose = htmlspecialchars($purpose, ENT_QUOTES, 'UTF-8');
    $collateral = htmlspecialchars($collateral, ENT_QUOTES, 'UTF-8');
    $penalty = htmlspecialchars($penalty, ENT_QUOTES, 'UTF-8');
    $receive = htmlspecialchars($receive, ENT_QUOTES, 'UTF-8');

    if (!is_numeric($loan) || $loan <= 0) {
        ob_end_clean();
        echo '<h1>Error</h1><p>Invalid loan amount</p>';
        exit;
    }
    if (!DateTime::createFromFormat('Y-m-d', $disburse)) {
        ob_end_clean();
        echo '<h1>Error</h1><p>Invalid disbursement date</p>';
        exit;
    }
    if (!is_numeric($day) || $day < 1 || $day > 31) {
        ob_end_clean();
        echo '<h1>Error</h1><p>Invalid due day</p>';
        exit;
    }
    if (!is_numeric($installment) || $installment <= 0) {
        ob_end_clean();
        echo '<h1>Error</h1><p>Invalid number of installments</p>';
        exit;
    }

    $monthlyRate = ($interest / 100) / 12;
    if ($monthlyRate > 0) {
        $permonth = $loan * ($monthlyRate * pow(1 + $monthlyRate, $installment)) / (pow(1 + $monthlyRate, $installment) - 1);
    } else {
        $permonth = $loan / $installment;
    }

    $receiveDate = new DateTime($disburse);
    $startdate = clone $receiveDate;
    $startdate->modify('first day of next month');
    $startdate->setDate($startdate->format('Y'), $startdate->format('m'), $day);

    if ($startdate < $receiveDate) {
        $startdate->modify('+1 month');
    }

    $endDate = clone $startdate;
    $endDate->modify('+' . ($installment - 1) . ' months');

    try {
        $stmt_vendor = $pdo->prepare("SELECT vendor_name, address FROM vendor WHERE vendor_id = :vendorID");
        $stmt_vendor->execute([':vendorID' => $vendor]);
        $vendor_data = $stmt_vendor->fetch(PDO::FETCH_ASSOC);

        if ($vendor_data) {
            $lender_name = $vendor_data['vendor_name'];
            $lender_address = $vendor_data['address'];
        } else {
            $lender_name = '[Lender Name]';
            $lender_address = '[Lender Address]';
        }

      
        $pdf = new LoanPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);

        $pdf->Write(5, 'This Loan Agreement ("Agreement") is made and entered into this ');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Write(5, $receiveDate->format('jS day of F, Y'));
        $pdf->SetFont('Arial', '', 10);
        $pdf->Write(5, ', by and between:');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Ln(4);

        $pdf->AddBold('LENDER:');
        $pdf->AddLine('__' . $lender_name . '__, a [corporation/individual] duly organized and existing under the laws of the Republic of the Philippines, with principal office at __' . $lender_address . '__ (hereinafter referred to as the "Lender");');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(Tagapagpautang: __' . $lender_name . '__, isang [korporasyon/indibidwal] na alinsunod sa mga batas ng Republika ng Pilipinas, na may pangunahing tanggapan sa __' . $lender_address . '__ (na tatawaging "Tagapagpautang").)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(2);

        $pdf->AddBold('AND / At');
        $pdf->AddBold('BORROWER:');
        $pdf->AddLine('__Slate Transportation Vehicle__, a [corporation/individual] duly organized and existing under the laws of the Republic of the Philippines, with principal office at __123 Rotonda Street, Quezon City__ (hereinafter referred to as the "Borrower").');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(Nanghihiram: __Slate Transportation Vehicle__, isang [korporasyon/indibidwal] na alinsunod sa mga batas ng Pilipinas, na may pangunahing tanggapan sa __123 Rotonda Street, Quezon City__ (na tatawaging "Nanghihiram").)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(5);

        $pdf->AddLine('The Lender and Borrower may hereinafter be collectively referred to as the "Parties" and individually as a "Party."');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(Ang Tagapagpautang at Nanghihiram ay maaaring tawagin na "Mga Panig" at paisa-isa bilang "Panig.")');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article I – Loan Amount and Purpose');
        $pdf->AddLine('1.1 The Lender agrees to extend to the Borrower a loan in the principal amount of __ PHP' . number_format($loan, 2) . '__. (the "Loan").');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(1.1 Sumasang-ayon ang Tagapagpautang na magpahiram sa Nanghihiram ng halagang __ PHP' . number_format($loan, 2) . '__ ("Pautang").)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('1.2 The Loan shall be used exclusively for the purpose of __' . $purpose . '.__ The Borrower shall not divert the proceeds to any unlawful or unauthorized purpose.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(1.2 Ang Pautang ay gagamitin lamang para sa layunin ng __' . $purpose . '.__ Ipinagbabawal ang paggamit nito sa anumang ilegal o hindi pinahihintulutang layunin.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article II – Disbursement');
        $pdf->AddLine('2.1 The Loan proceeds shall be disbursed on or before __ ' . $receiveDate->format('F j, Y') . '__  via __ ' . $receive . '__ , subject to the Borrower\'s compliance with all conditions precedent.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(2.1 Ang halaga ng Pautang ay ibibigay sa o bago ang __ ' . $receiveDate->format('F j, Y') . '__  sa pamamagitan ng __ ' . $receive . '__ , kung natugunan ng Nanghihiram ang lahat ng kinakailangan.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('2.2 The Lender reserves the right to withhold disbursement if the Borrower fails to submit required documents or collateral.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(2.2 May karapatan ang Tagapagpautang na ipagpaliban ang paglabas ng Pautang kung hindi naibigay ng Nanghihiram ang mga kinakailangang dokumento o kolateral.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article III – Interest and Charges');
        $pdf->AddLine('3.1 The Loan shall bear interest at the rate of __ ' . $interest . '%__  per annum, computed on the outstanding balance.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(3.1 Ang Pautang ay magkakaroon ng interes sa rate na __ ' . $interest . '%__  kada taon, na kinakalkula batay sa natitirang balanse.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('3.2 Interest shall accrue from the date of disbursement and be payable in accordance with the repayment schedule.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(3.2 Ang interes ay magsisimula mula sa petsa ng pagbibigay at babayaran ayon sa iskedyul ng pagbabayad.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('3.3 The Borrower shall be responsible for all ancillary charges, including documentary stamp tax, bank charges, and legal fees, if any.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(3.3 Ang Nanghihiram ang mananagot sa lahat ng karagdagang bayarin, kabilang ang documentary stamp tax, bayarin sa bangko, at bayarin sa legal, kung mayroon.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article IV – Repayment');
        $pdf->AddLine('4.1 The Borrower shall repay the Loan in __ ' . $installment . '__  equal installments of PHP __ ' . number_format($permonth, 2) . '__ , due on the __ ' . $day . '__  day of each month, commencing on __ ' . $startdate->format('F j, Y') . '__ .');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(4.1 Ang Nanghihiram ay magbabayad ng Pautang sa __ ' . $installment . '__  na pantay na hulugan ng PHP __ ' . number_format($permonth, 2) . '__ , na dapat bayaran sa ika-__ ' . $day . '__  araw ng bawat buwan, simula sa __ ' . $startdate->format('F j, Y') . '__ .)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('4.2 Payments shall be applied first to accrued interest, then to principal, and finally to penalties or other charges.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(4.2 Ang mga bayarin ay uunahin sa naipon na interes, pagkatapos sa prinsipal, at panghuli sa mga parusa o iba pang bayarin.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article V – Security');
        $pdf->AddLine('5.1 The Loan shall be secured by the following collateral: __ ' . $collateral . '__ .');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(5.1 Ang Pautang ay sisiguruhin ng sumusunod na kolateral: __ ' . $collateral . '__ .)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('5.2 The Borrower authorizes the Lender to enforce the security in case of default, without need of prior demand or judicial action, subject to applicable Philippine laws.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(5.2 Pinahintulutan ng Nanghihiram ang Tagapagpautang na ipatupad ang kolateral sa kaso ng default, nang walang pangangailangan ng paunang kahilingan o aksyong hudisyal, alinsunod sa naaangkop na batas ng Pilipinas.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article VI – Representations and Warranties');
        $pdf->AddLine('The Borrower hereby represents and warrants that:');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('Ang Nanghihiram ay nagpapahayag at ginagarantiyahan na:');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- It has full power, legal capacity, and authority to enter into and perform its obligations under this Agreement.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Ito ay may buong kapangyarihan, legal na kapasidad, at awtoridad na pumasok at tuparin ang mga obligasyon nito sa ilalim ng Kasunduang ito.');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- The execution of this Agreement has been duly authorized.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Ang pagpapatupad ng Kasunduang ito ay naaayon na awtorisado.');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- All information and documents provided are true, accurate, and complete.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Lahat ng impormasyon at dokumentong ibinigay ay totoo, tumpak, at kumpleto.');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article VII – Events of Default');
        $pdf->AddLine('The Borrower shall be deemed in default upon occurrence of any of the following:');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('Ang Nanghihiram ay ituturing na nasa default kapag nangyari ang alinman sa mga sumusunod:');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- Failure to pay any installment when due.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Pagkabigo sa pagbabayad ng anumang hulugan kapag due na.');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- Breach of any representation, warranty, or obligation herein.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Paglabag sa anumang representasyon, garantiya, o obligasyon dito.');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(2);
        $pdf->AddLine('Upon default, the Lender may, at its sole discretion:');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('Sa kaso ng default, ang Tagapagpautang ay maaaring, sa kanyang sariling pagpapasya:');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- Declare the entire outstanding Loan immediately due and payable.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Ipahayag na ang buong natitirang Pautang ay agarang due at babayaran.');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- Enforce rights against the collateral.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Ipataw ang mga karapatan laban sa kolateral.');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('- Impose penalty interest at the rate of __ ' . $penaltyInterest . '%__  per month on overdue amounts.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('- Magpataw ng parusang interes sa rate na __ ' . $penaltyInterest . '%__  kada buwan sa mga overdue na halaga.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article VIII – Penalties and Fees');
        $pdf->AddLine('8.1 Late payments shall incur a penalty of __ ' . $penalty . '__ .');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(8.1 Ang mga late na bayarin ay magkakaroon ng parusa na __ ' . $penalty . '__ .)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('8.2 All expenses incurred in enforcing this Agreement, including attorney\'s fees and court costs, shall be charged to the Borrower.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(8.2 Lahat ng gastusin sa pagpapatupad ng Kasunduang ito, kabilang ang bayarin sa abogado at gastos sa korte, ay sisingilin sa Nanghihiram.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article IX – Governing Law and Dispute Resolution');
        $pdf->AddLine('9.1 This Agreement shall be governed by and construed in accordance with the laws of the Republic of the Philippines.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(9.1 Ang Kasunduang ito ay pamamahalaan at ipapaliwanag alinsunod sa mga batas ng Republika ng Pilipinas.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('9.2 Any dispute shall be resolved through amicable settlement, failing which it shall be submitted to arbitration or to the exclusive jurisdiction of the courts of the Philippines.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(9.2 Anumang hindi pagkakasundo ay lutasin sa pamamagitan ng maayos na pamamaraan, at kung hindi ito malutas, ito ay isusumite sa arbitrasyon o sa eksklusibong hurisdiksyon ng mga korte ng Pilipinas.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->ChapterTitle('Article X – Miscellaneous');
        $pdf->AddLine('10.1 Notices – All notices shall be in writing and delivered personally, by courier, or registered mail to the addresses provided herein.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(10.1 Mga Abiso – Lahat ng abiso ay dapat nakasulat at ihahatid nang personal, sa pamamagitan ng courier, o rehistradong koreo sa mga address na ibinigay dito.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('10.2 Severability – If any provision is held invalid, the remainder shall continue in effect.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(10.2 Separabilidad – Kung ang anumang probisyon ay itinuring na hindi wasto, ang natitira ay mananatiling may bisa.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('10.3 Amendments – Any modification must be in writing and signed by both Parties.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(10.3 Mga Pagbabago – Anumang pagbabago ay dapat nakasulat at nilagdaan ng parehong Panig.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->AddLine('10.4 Entire Agreement – This Agreement embodies the entire understanding between the Parties.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(10.4 Buong Kasunduan – Ang Kasunduang ito ay naglalaman ng buong pag-unawa sa pagitan ng mga Panig.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);

        $pdf->AddPage();
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'SIGNATURES / LAGDA', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 6, 
            "IN WITNESS WHEREOF, the Parties have hereunto affixed their signatures on the date and place first above written.\n" .
            "Bilang patunay, ang mga Panig ay lumagda sa kasunduang ito sa petsa at lugar na nakasaad sa itaas.", 
            0, 'C'
        );
        $pdf->Ln(10);

        $fieldsSign = [
            'Signature / Lagda: __________________________',
            'Name / Pangalan: ___________________________',
            'Designation / Katungkulan: __________________',
            'Date / Petsa: _______________________________'
        ];
        $pdf->AddAlignedBlocks('Lender / Tagapagpautang', 'Borrower / Nanghihiram', $fieldsSign);

        $fieldsWitness = [
            'Signature / Lagda: __________________________',
            'Name / Pangalan: ___________________________'
        ];
        $pdf->AddAlignedBlocks('Witness 1 / Saksi 1', 'Witness 2 / Saksi 2', $fieldsWitness);

        $pdf->AddPage();
        $pdf->ChapterTitle('Acknowledgment / Pagpapatibay');
        $pdf->AddLine('Republic of the Philippines / Republika ng Pilipinas )');
        $pdf->AddLine('Province of [Province] / Lalawigan ng [Province] ) S.S.');
        $pdf->AddLine('City/Municipality of [City] / Lungsod/Bayan ng [City] )');
        $pdf->Ln(5);
        $pdf->AddLine('BEFORE ME, a Notary Public for and in the City/Municipality of [City], this _________________________ day of____________ , personally appeared:');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(SA HARAP KO, isang Notary Public sa Lungsod/Bayan ng [City], ngayong ika-_________________________ ng ____________, ay personal na humarap:)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(5);
        $pdf->AddLine('Name / Pangalan: ' . $lender_name);
        $pdf->AddLine('Competent Evidence of Identity / Katibayan ng Pagkakakilanlan: __________________');
        $pdf->AddLine('Issued on / Inisyu noong: _______________________');
        $pdf->AddLine('Issued at / Inisyu sa: ________________________');
        $pdf->Ln(5);
        $pdf->AddLine('Name / Pangalan: Slate Transportation Vehicle');
        $pdf->AddLine('Competent Evidence of Identity / Katibayan ng Pagkakakilanlan: __________________');
        $pdf->AddLine('Issued on / Inisyu noong: _______________________');
        $pdf->AddLine('Issued at / Inisyu sa: ________________________');
        $pdf->Ln(5);
        $pdf->AddLine('Known to me and to me known to be the same persons who executed the foregoing Loan Agreement consisting of [Number] pages, including this page where this acknowledgment is written, and they acknowledged to me that the same is their free and voluntary act and deed.');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->AddLine('(Na aking nakilala at kinilala na sila rin ang mga taong lumagda sa nasabing Kasunduan sa Pautang na binubuo ng [Number] pahina, kabilang ang pahinang ito kung saan nakasulat ang pagpapatibay na ito, at kinilala nila sa akin na ito ay kanilang malaya at kusang-loob na gawa.)');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(10);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);

        $safeLoanTitle = preg_replace('/[^A-Za-z0-9_-]/', '_', $loantitle);
        $filename = 'loan_' . $safeLoanTitle . '_' . time() . '.pdf';
        $filePath = '../../pdfs/' . $filename;

        if (!is_dir('../../pdfs')) {
            mkdir('../../pdfs', 0775, true);
        }

        $pdfOutput = $pdf->Output('', 'S');
        if (file_put_contents($filePath, $pdfOutput) === false) {
            ob_end_clean();
            echo '<h1>Error</h1><p>Error saving PDF to folder. Check permissions on pdfs/ folder.</p>';
            exit;
        }

        $sql = "INSERT INTO loan (
                    LoanTitle, loanAmount, interestRate, startDate, EndDate, Notes,
                    VendorID, dateReceived, paymentMethod, installment,
                    AmountperMonth, dueday, collateral, PenaltyInterest, PenaltyDetails, pdf_filename
                )
                VALUES (
                    :title, :loan, :interest, :startDate, :EndDate, :notes,
                    :vendorID, :dateReceive, :method, :installment,
                    :Amountper, :dueday, :collatera, :penalty, :detail, :pdf_filename
                )";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            ':title' => $loantitle,
            ':loan' => $loan,
            ':interest' => $interest,
            ':startDate' => $startdate->format('Y-m-d'),
            ':EndDate' => $endDate->format('Y-m-d'),
            ':installment' => $installment,
            ':notes' => $purpose,
            ':vendorID' => $vendor,
            ':dateReceive' => $disburse,
            ':method' => $receive,
            ':Amountper' => $permonth,
            ':dueday' => $day,
            ':collatera' => $collateral,
            ':penalty' => $penaltyInterest,
            ':detail' => $penalty,
            ':pdf_filename' => 'pdfs/' . $filename
        ]);

        if ($success) {
            ob_end_clean();
            echo '<script>window.location.href = "loan.php?success=1&message=' . urlencode('Loan agreement created successfully') . '";</script>';
            exit;
        } else {
            ob_end_clean();
            echo '<h1>Error</h1><p>Failed to insert data into the database.</p>';
            exit;
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo '<h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
}

$success = isset($_GET['success']) && $_GET['success'] == 1;
$message = $success ? urldecode($_GET['message']) ?? 'Loan agreement created successfully' : '';
?>


