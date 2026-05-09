<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/config.php';

require_role('trader');
$title = 'KYC';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">KYC Verification</h1>
    <form class="space-y-3 bg-slate-900 border border-slate-800 rounded-xl p-6" action="<?= APP_URL ?>/api/submit_kyc.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input class="w-full bg-slate-800 rounded px-3 py-2" name="full_name" placeholder="Full Name (as shown on ID)" required>
        <input class="w-full bg-slate-800 rounded px-3 py-2" type="date" name="date_of_birth" required>
        <input class="w-full bg-slate-800 rounded px-3 py-2" name="nationality" placeholder="Nationality" required>
        <textarea class="w-full bg-slate-800 rounded px-3 py-2" name="address_line" placeholder="Complete Address" required></textarea>
        <div class="grid md:grid-cols-3 gap-3">
            <input class="w-full bg-slate-800 rounded px-3 py-2" name="city" placeholder="City" required>
            <input class="w-full bg-slate-800 rounded px-3 py-2" name="province" placeholder="Province" required>
            <input class="w-full bg-slate-800 rounded px-3 py-2" name="postal_code" placeholder="Postal Code" required>
        </div>
        <input class="w-full bg-slate-800 rounded px-3 py-2" name="contact_number" placeholder="Contact Number" required>
        <input class="w-full bg-slate-800 rounded px-3 py-2" name="occupation" placeholder="Occupation" required>
        <input class="w-full bg-slate-800 rounded px-3 py-2" name="source_of_funds" placeholder="Source of Funds" required>
        <input class="w-full bg-slate-800 rounded px-3 py-2" name="id_type" placeholder="ID Type" required>
        <input class="w-full bg-slate-800 rounded px-3 py-2" name="id_number" placeholder="ID Number" required>
        <label class="block text-sm text-slate-300">Valid ID Image</label>
        <input class="w-full" type="file" name="id_image" accept="image/*" required>
        <label class="block text-sm text-slate-300">Selfie Image</label>
        <input class="w-full" type="file" name="selfie_image" accept="image/*" required>
        <button class="bg-indigo-600 px-4 py-2 rounded">Submit KYC</button>
    </form>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
