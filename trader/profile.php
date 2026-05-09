<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');

// Fetch user profile and KYC status
$stmt = db()->prepare('
    SELECT u.name, u.email, u.profile_image, k.status as kyc_status 
    FROM users u 
    LEFT JOIN kyc_verifications k ON k.user_id = u.id 
    WHERE u.id = :id 
    LIMIT 1
');
$stmt->execute(['id' => $user['id']]);
$profile = $stmt->fetch();

$profileImage = (string) ($profile['profile_image'] ?? '');
$kycStatus = (string) ($profile['kyc_status'] ?? 'unverified');

$title = 'Profile Settings | Vortex';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-accent-cyan font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-accent-cyan shadow-[0_0_8px_#00B5D8]"></span>
            Personal Identity
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Account Profile</h1>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar: Profile Overview -->
        <div class="space-y-8">
            <div class="glass-card rounded-[40px] p-10 text-center relative overflow-hidden group">
                <div
                    class="absolute inset-0 bg-gradient-to-b from-accent-blue/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                </div>

                <div class="relative inline-block mb-8">
                    <?php if ($profileImage !== ''): ?>
                        <img src="<?= APP_URL . '/' . htmlspecialchars($profileImage) ?>" alt="Profile"
                            class="w-32 h-32 rounded-[40px] object-cover border-2 border-accent-blue/30 shadow-2xl">
                    <?php else: ?>
                        <div
                            class="w-32 h-32 rounded-[40px] bg-white/5 border-2 border-white/10 flex items-center justify-center text-4xl text-white/20 font-black">
                            <?= strtoupper(substr($profile['name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <button onclick="document.getElementById('profile_image_input').click()"
                        class="absolute -bottom-2 -right-2 w-10 h-10 rounded-2xl bg-accent-blue text-white flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                </div>

                <h3 class="text-2xl font-black text-white mb-1"><?= htmlspecialchars($profile['name'] ?? 'Trader') ?>
                </h3>
                <p class="text-sm text-white/40 mb-8"><?= htmlspecialchars($profile['email'] ?? '') ?></p>

                <div
                    class="flex items-center justify-center gap-2 px-6 py-3 rounded-2xl <?= $kycStatus === 'verified' ? 'bg-growth-green/10 text-growth-green' : 'bg-rose-500/10 text-rose-500' ?> border border-current/10">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    <span class="text-xs font-black tracking-widest uppercase"><?= htmlspecialchars($kycStatus) ?>
                        Account</span>
                </div>
            </div>

            <!-- Verification Status / Call to Action -->
            <?php if ($kycStatus !== 'verified'): ?>
                <div class="glass-card rounded-[40px] p-8 bg-accent-cyan/5 border-accent-cyan/20">
                    <h4 class="text-lg font-black text-white mb-3">Identity Verification</h4>
                    <p class="text-sm text-white/60 mb-6 leading-relaxed">Complete your KYC to unlock higher withdrawal
                        limits and advanced trading features.</p>
                    <button onclick="toggleModal('kycModal')"
                        class="inline-block w-full py-4 rounded-2xl bg-accent-cyan text-deep font-black text-center text-sm tracking-widest hover:bg-opacity-90 transition-all">START
                        VERIFICATION</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content: Forms -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Profile Settings -->
            <section class="glass-card rounded-[40px] p-10">
                <h2 class="text-xl font-black text-white mb-8 flex items-center gap-3">
                    <svg class="w-6 h-6 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Profile Settings
                </h2>
                <form action="<?= APP_URL ?>/api/profile_update.php" method="post" enctype="multipart/form-data"
                    class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="file" id="profile_image_input" name="profile_image" accept="image/*" class="hidden">

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Display Name</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/10 focus:border-accent-blue outline-none text-white font-bold transition-all"
                                name="name" value="<?= htmlspecialchars((string) $profile['name']) ?>" required>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Email Address</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/10 focus:border-accent-blue outline-none text-white font-bold transition-all"
                                type="email" name="email" value="<?= htmlspecialchars((string) $profile['email']) ?>"
                                required>
                        </div>
                    </div>
                    <button
                        class="px-10 py-4 rounded-2xl bg-accent-blue text-white font-black text-sm tracking-widest hover:shadow-2xl hover:shadow-accent-blue/40 transition-all">SAVE
                        CHANGES</button>
                </form>
            </section>

            <!-- Password Security -->
            <section class="glass-card rounded-[40px] p-10">
                <h2 class="text-xl font-black text-white mb-8 flex items-center gap-3">
                    <svg class="w-6 h-6 text-growth-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    Security Update
                </h2>
                <form action="<?= APP_URL ?>/api/change_password.php" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Current
                                Password</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/10 focus:border-growth-green outline-none text-white font-bold transition-all"
                                type="password" name="current_password" placeholder="Verify identity..." required>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">New Access Key</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/10 focus:border-growth-green outline-none text-white font-bold transition-all"
                                type="password" name="new_password" placeholder="Min. 8 characters" required
                                minlength="8">
                        </div>
                    </div>
                    <button
                        class="px-10 py-4 rounded-2xl bg-growth-green text-deep font-black text-sm tracking-widest hover:shadow-2xl hover:shadow-growth-green/40 transition-all">UPDATE
                        SECURITY</button>
                </form>
            </section>
        </div>
    </div>
</main>

<!-- KYC Modal -->
<div id="kycModal" class="modal-hidden">
    <div onclick="toggleModal('kycModal')" class="modal-overlay cursor-pointer"></div>
    <div class="modal-content !max-w-2xl">
        <section class="glass-card rounded-[40px] p-8 border-accent-cyan/10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-white flex items-center gap-3">
                    <svg class="w-6 h-6 text-accent-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    Identity Verification
                </h2>
                <div class="flex gap-1" id="kycSteps">
                    <span class="w-8 h-1 rounded-full bg-accent-cyan transition-all" id="stepIndicator1"></span>
                    <span class="w-8 h-1 rounded-full bg-white/10 transition-all" id="stepIndicator2"></span>
                </div>
            </div>

            <form id="kycForm" class="space-y-6" action="<?= APP_URL ?>/api/submit_kyc.php" method="post"
                enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                <!-- Step 1: Personal Details -->
                <div id="kycStep1" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                    <p class="text-xs font-black text-white/40 tracking-widest uppercase">Step 1: Personal Details</p>
                    <div class="space-y-3">
                        <label class="text-xs font-black tracking-widest text-white/60 ml-1">Legal Full Name</label>
                        <input
                            class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold"
                            name="full_name" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Date of Birth</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold"
                                type="date" name="date_of_birth" required>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Nationality</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold"
                                name="nationality" placeholder="e.g. Filipino" required>
                        </div>
                    </div>

                    <!-- Structured Address -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Region</label>
                            <select id="region_select" name="region" onchange="updateCities()"
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold appearance-none"
                                required>
                                <option value="">Select Region</option>
                                <option value="NCR">Metro Manila</option>
                                <option value="R3">Central Luzon</option>
                                <option value="R4A">CALABARZON</option>
                                <option value="R7">Central Visayas</option>
                                <option value="R11">Davao Region</option>
                            </select>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">City /
                                Municipality</label>
                            <select id="city_select" name="city" onchange="updateBarangays()"
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold appearance-none"
                                required>
                                <option value="">Select City</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Barangay</label>
                            <select id="barangay_select" name="barangay"
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold appearance-none"
                                required>
                                <option value="">Select Barangay</option>
                            </select>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Home Address</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold"
                                name="address_line" placeholder="Street, Unit, etc." required>
                        </div>
                    </div>

                    <button type="button" onclick="nextKycStep()"
                        class="w-full py-5 rounded-2xl bg-accent-cyan text-deep font-black text-sm tracking-widest hover:bg-opacity-90 transition-all">CONTINUE
                        TO UPLOADS</button>
                </div>

                <!-- Step 2: Verification Documents -->
                <div id="kycStep2" class="hidden space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                    <p class="text-xs font-black text-white/40 tracking-widest uppercase">Step 2: Verification Documents
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">Govt ID Type</label>
                            <select name="id_type"
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold appearance-none">
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="national_id">National ID</option>
                            </select>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-widest text-white/60 ml-1">ID Number</label>
                            <input
                                class="w-full px-6 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold"
                                name="id_number" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div onclick="document.getElementById('id_image_input').click()" id="id_upload_container"
                            class="border-2 border-dashed border-white/10 rounded-3xl p-6 text-center hover:border-accent-cyan/40 transition-all cursor-pointer bg-white/2 relative group">
                            <svg id="id_icon"
                                class="w-8 h-8 text-white/20 mx-auto mb-2 group-hover:text-accent-cyan transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <p id="id_status" class="text-[10px] font-black text-white/40 tracking-widest uppercase">ID
                                SCAN</p>
                            <input type="file" id="id_image_input" name="id_image" onchange="handleFileUpload('id')"
                                accept="image/*" required class="hidden">
                        </div>
                        <div onclick="document.getElementById('selfie_image_input').click()"
                            id="selfie_upload_container"
                            class="border-2 border-dashed border-white/10 rounded-3xl p-6 text-center hover:border-accent-cyan/40 transition-all cursor-pointer bg-white/2 relative group">
                            <svg id="selfie_icon"
                                class="w-8 h-8 text-white/20 mx-auto mb-2 group-hover:text-accent-cyan transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                            <p id="selfie_status"
                                class="text-[10px] font-black text-white/40 tracking-widest uppercase">SELFIE</p>
                            <input type="file" id="selfie_image_input" name="selfie_image"
                                onchange="handleFileUpload('selfie')" accept="image/*" required class="hidden">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-4">
                        <button type="button" onclick="prevKycStep()"
                            class="flex-1 py-5 rounded-2xl bg-white/5 text-white font-black text-sm tracking-widest hover:bg-white/10 transition-all">BACK</button>
                        <button type="submit"
                            class="flex-[2] py-5 rounded-2xl bg-accent-cyan text-deep font-black text-sm tracking-widest hover:shadow-2xl shadow-accent-cyan/20 transition-all">SUBMIT</button>
                    </div>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
    const locationData = {
        'NCR': {
            'Manila': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
            'Quezon City': ['Bago Bantay', 'Bagong Silangan', 'Batasan Hills']
        },
        'R3': {
            'Angeles': ['Balibago', 'Lourdes', 'Sapa Libutad'],
            'San Fernando': ['Sindalan', 'Dolores', 'San Agustin']
        },
        'R4A': {
            'Antipolo': ['Dela Paz', 'San Jose', 'Mambugan'],
            'Calamba': ['Canlubang', 'Parian', 'Real']
        }
    };

    function updateCities() {
        const region = document.getElementById('region_select').value;
        const citySelect = document.getElementById('city_select');
        const barangaySelect = document.getElementById('barangay_select');

        citySelect.innerHTML = '<option value="">Select City</option>';
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

        if (locationData[region]) {
            Object.keys(locationData[region]).forEach(city => {
                citySelect.innerHTML += `<option value="${city}">${city}</option>`;
            });
        }
    }

    function updateBarangays() {
        const region = document.getElementById('region_select').value;
        const city = document.getElementById('city_select').value;
        const barangaySelect = document.getElementById('barangay_select');

        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

        if (locationData[region] && locationData[region][city]) {
            locationData[region][city].forEach(brgy => {
                barangaySelect.innerHTML += `<option value="${brgy}">${brgy}</option>`;
            });
        }
    }

    function handleFileUpload(type) {
        const input = document.getElementById(`${type}_image_input`);
        const status = document.getElementById(`${type}_status`);
        const icon = document.getElementById(`${type}_icon`);
        const container = document.getElementById(`${type}_upload_container`);

        if (input.files && input.files[0]) {
            status.innerText = input.files[0].name.substring(0, 15) + '...';
            status.classList.replace('text-white/40', 'text-growth-green');
            icon.classList.replace('text-white/20', 'text-growth-green');
            container.classList.add('border-growth-green/40', 'bg-growth-green/5');
        }
    }

    // Form Submission Feedback
    document.getElementById('kycForm').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerText = 'PROCESSING AUDIT...';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    });

    window.addEventListener('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('kyc') === 'success') {
            showToast('Identity Verification Protocol Submitted Successfully.', 'success');
        } else if (urlParams.get('error') === 'kyc_failed') {
            showToast('Submission Failed. Please verify your credentials and try again.', 'error');
        }
    });

    function toggleModal(id) {
        const modal = document.getElementById(id);
        modal.classList.toggle('modal-hidden');
        document.body.style.overflow = modal.classList.contains('modal-hidden') ? '' : 'hidden';
    }

    function nextKycStep() {
        document.getElementById('kycStep1').classList.add('hidden');
        document.getElementById('kycStep2').classList.remove('hidden');
        document.getElementById('stepIndicator1').classList.replace('bg-accent-cyan', 'bg-white/20');
        document.getElementById('stepIndicator2').classList.replace('bg-white/10', 'bg-accent-cyan');
    }

    function prevKycStep() {
        document.getElementById('kycStep2').classList.add('hidden');
        document.getElementById('kycStep1').classList.remove('hidden');
        document.getElementById('stepIndicator2').classList.replace('bg-accent-cyan', 'bg-white/10');
        document.getElementById('stepIndicator1').classList.replace('bg-white/20', 'bg-accent-cyan');
    }
</script>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>