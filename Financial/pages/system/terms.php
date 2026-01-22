<div id="darkOverlay"></div>
<?php if ($show_terms_popup): ?>

<div id="termsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800/50">
    <div class="relative max-h-[90vh] w-full max-w-3xl mx-auto bg-white rounded-xl shadow-xl overflow-hidden border border-gray-200 flex flex-col">
        <!-- Sticky Header -->
        <header class="sticky top-0 z-10 bg-white p-6 border-b border-gray-200">
            <div>
                <h2 class="text-xl font-bold text-gray-900">SLATE Freight Management — Terms & Conditions</h2>
                <p class="text-sm text-gray-500">Financial, Data Privacy, and Legal Compliance (Philippines)</p>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="p-6 overflow-y-auto flex-1">
            <div class="modal-scroll max-h-[55vh] bg-gray-50 border border-gray-200 rounded-lg p-6 leading-relaxed text-gray-700 space-y-6">
                <section>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">SLATE Freight Management — Overview</h3>
                    <p class="text-sm italic text-gray-600">Last Updated: October 2025 | Compliant with Philippine Financial and Data Privacy Laws.</p>
                    <p><strong>SLATE</strong> is a capstone prototype assisting freight companies with financial automation, forecasting, and analytics using ChatGPT and TensorFlow. It is for academic, research, and demonstration use only.</p>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">1. Purpose</h4>
                    <p>The system simulates real-world freight financial processes including disbursement, budgeting, and forecasting. Outputs are illustrative and not official financial statements.</p>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">2. User Responsibilities</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Provide accurate and verifiable financial and operational data.</li>
                        <li>Use the system ethically and in compliance with Philippine financial laws.</li>
                        <li>Do not falsify, tamper, or misrepresent data or system outputs.</li>
                        <li>Fraudulent actions may lead to academic or legal sanctions.</li>
                    </ul>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">3. Data Privacy & Protection</h4>
                    <p>SLATE follows the <strong>Data Privacy Act of 2012 (RA 10173)</strong>. All data is collected and processed solely for research and testing purposes.</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Only minimal, necessary data is gathered.</li>
                        <li>Data will not be shared without explicit user consent.</li>
                        <li>Users may request access, correction, or deletion of data.</li>
                        <li>Confidentiality and protection are ensured through safeguards consistent with Philippine data privacy standards.</li>
                    </ul>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">4. Financial Compliance & Anti-Money Laundering (AML)</h4>
                    <p>SLATE aligns with the <strong>Anti-Money Laundering Act of 2001 (RA 9160)</strong> and relevant BSP regulations. Transactions must follow lawful and verifiable documentation standards.</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>All entries should be supported by purchase orders, receipts, or approvals.</li>
                        <li>Suspicious or illegal financial activity is prohibited.</li>
                        <li>Developers may report potential violations to relevant authorities.</li>
                    </ul>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">5. Record Keeping & Audit</h4>
                    <p>System-generated transactions may undergo review in compliance with BSP audit and record-keeping policies.</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Maintain supporting records (e.g., vouchers, invoices, receipts).</li>
                        <li>SLATE reserves the right to verify records for academic or compliance validation.</li>
                    </ul>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">6. Data Security & IT Risk Management</h4>
                    <p>SLATE adopts safeguards inspired by BSP’s IT Risk Management Framework. As a prototype, it does not meet full enterprise-grade standards.</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Users must secure their own credentials.</li>
                        <li>Developers are not liable for breaches beyond their control.</li>
                    </ul>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">7. System Limitations</h4>
                    <p>Forecasts and reports are illustrative, depending on input quality. They should not replace licensed accounting or financial systems.</p>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">8. Intellectual Property</h4>
                    <p>All software components and algorithms are the intellectual property of the SLATE development team and affiliated academic institution. Unauthorized reproduction or commercial use violates <strong>RA 8293 (Intellectual Property Code of the Philippines)</strong>.</p>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">9. Sanctions & Penalties</h4>
                    <p>Violations such as data falsification or misuse are subject to disciplinary action and may be reported under RA 10173 and RA 9160.</p>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">10. Governing Law & Dispute Resolution</h4>
                    <p>These terms are governed by Philippine law. Disputes shall be handled under the jurisdiction of the courts in the National Capital Region (NCR), Philippines.</p>
                </section>

                <section>
                    <h4 class="text-lg font-semibold text-indigo-700 uppercase tracking-wide mb-1">11. Acceptance of Terms</h4>
                    <p>By using SLATE, users acknowledge that they have read, understood, and agreed to these terms and consent to lawful processing under Philippine data and financial laws.</p>
                </section>
            </div>
        </div>

        <!-- Fixed Footer -->
        <div class="p-6 border-t border-gray-200 bg-white">
            <div class="flex justify-between items-center">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input id="confirmRead" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>I have read and agree to the Terms & Conditions</span>
                </label>
                <button id="agreeBtn" class="px-4 py-2 bg-indigo-500 text-white rounded-md text-sm focus:ring-2 focus:ring-indigo-400 disabled:opacity-50" disabled>
                    Agree & Continue
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const confirmReadCheckbox = document.getElementById('confirmRead');
const agreeBtn = document.getElementById('agreeBtn');
const termsModal = document.getElementById('termsModal');
const darkOverlay = document.getElementById('darkOverlay');
const body = document.body;


if (termsModal && <?php echo $show_terms_popup ? 'true' : 'false'; ?>) {
    body.classList.add('modal-open');
}


confirmReadCheckbox.addEventListener('change', () => {
    agreeBtn.disabled = !confirmReadCheckbox.checked;
});


agreeBtn.addEventListener('click', async () => {
    if (!confirmReadCheckbox.checked) return;
    try {
        const response = await fetch('../auth/agree_terms.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        });
        if (response.ok) {
            termsModal.style.display = 'none';
            body.classList.remove('modal-open');
        } else {
            alert('Error saving your agreement. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again later.');
    }
});

termsModal.addEventListener('click', (e) => {
    if (e.target === termsModal) e.preventDefault();
});

</script>

<?php endif; ?>