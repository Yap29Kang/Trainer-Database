<?php
/**
 * Common layout template
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Trainer Database'; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- HEADER -->
<header>
    <div class="hdr-top">
        <div class="logo">Trainer<span> Database</span></div>
        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
            <button class="hdr-btn" id="authHdrBtn" onclick="handleAuthButton()">
                <?php echo ($_SESSION['role'] === 'admin' ? 'Log Out' : 'Log In'); ?>
            </button>
            <button class="hdr-btn" id="upHdrBtn" onclick="openUpload()" style="display:none;">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                Upload Excel
            </button>
        </div>
    </div>
    <div class="hdr-summary">
        <div class="sum-main">
            <div class="sum-main-num" id="cntTotal">0</div>
            <div class="sum-main-lbl">Training Providers</div>
        </div>
        <div class="sum-stats">
            <div class="ss"><div class="ss-num z" id="cntActive">0</div><div class="ss-lbl">Active</div></div>
            <div class="ss"><div class="ss-num y" id="cntGrey">0</div><div class="ss-lbl">Greylist</div></div>
            <div class="ss"><div class="ss-num r" id="cntBlack">0</div><div class="ss-lbl">Blacklisted</div></div>
        </div>
    </div>
</header>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="sw">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input class="si" id="si" type="text" placeholder="Search Training Provider or Trainers" oninput="handleSearch()">
    </div>
    <div class="vt">
        <button class="vtb active" onclick="setView('prov', this)">Training Providers</button>
        <button class="vtb" onclick="setView('train', this)">Trainers / Speakers</button>
    </div>
    <div class="sf-wrap">
        <span class="sf-lbl">Status:</span>
        <select class="ss-sel" id="sfSel" onchange="handleSearch()">
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="greylist">Greylist</option>
            <option value="blacklisted">Blacklisted</option>
        </select>
    </div>
    <button class="sort-btn" onclick="toggleSort()">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
        <span id="sortLbl">A → Z</span>
    </button>
    <button class="dl-btn" id="dlBtn" onclick="downloadExport()">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
        Download
    </button>
</div>

<!-- MAIN CONTENT -->
<?php
if (isset($main_content) && trim($main_content) !== '') {
    echo $main_content;
}

if (isset($content_file) && is_file($content_file)) {
    include $content_file;
}
?>

<!-- ════════════════════════════════════
     PROVIDER MODAL
════════════════════════════════════ -->
<div class="ov" id="provOv" onclick="if(event.target===this)closeModal()">
    <div class="pm">
        <div class="mh">
            <div><div class="mpn" id="mN">—</div></div>
            <button class="mc" onclick="closeModal()">✕</button>
        </div>
        <div class="mst" id="mStatRow">
            <div class="msb" id="mStBub">
                <div class="msb-n" id="mSt">—</div>
                <div class="msb-l">Status</div>
                <div class="msb-h hidden" id="mStHint"></div>
            </div>
            <div class="msb" id="mPaBub">
                <div class="msb-n" id="mPa">—</div>
                <div class="msb-l">Total Participants</div>
                <div class="msb-h hidden" id="mPaHint"></div>
            </div>
            <div class="msb" id="mExpBub">
                <div class="msb-n" id="mExp">—</div>
                <div class="msb-l">Area of Expertise</div>
                <div class="msb-h hidden" id="mExpHint"></div>
            </div>
            <div class="msb" id="mExp2Bub">
                <div class="msb-n" id="mExp2">—</div>
                <div class="msb-l">Area of Expertise</div>
                <div class="msb-h hidden" id="mExp2Hint"></div>
            </div>
        </div>
        <div class="ptabs">
            <button class="ptab active" id="ptab-courses" onclick="switchProviderTab('courses', this)">Course History</button>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="ptab" id="ptab-status" onclick="switchProviderTab('status', this)">Status History</button>
            <button class="ptab" id="ptab-remarks" onclick="switchProviderTab('remarks', this)">Remarks</button>
            <?php endif; ?>
        </div>

        <div class="ptab-panels">
            <div class="ptab-panel active" id="providerTab-courses">
                <div class="mb2">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <div id="yearHeading"></div>
                        <div class="mar">
                            <span style="font-size:.75rem;color:var(--muted);font-family:'Calibri',sans-serif">Filter year:</span>
                            <select class="ysel" id="ySel" onchange="renderHistory()"><option value="all">All Years</option></select>
                        </div>
                    </div>
                    <div id="histC"></div>
                </div>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="ptab-panel" id="providerTab-status">
                <div class="mb2">
                    <div id="statusHistC"></div>
                </div>
            </div>
            <div class="ptab-panel" id="providerTab-remarks">
                <div class="mb2">
                    <div id="provRemarksC"></div>
                    <div class="remark-form" id="provRemarkForm" style="display:<?php echo ($_SESSION['role'] === 'admin' ? 'block' : 'none'); ?>;">
                        <textarea class="remark-input" id="provRemarkInput" rows="4" placeholder="Write a participant remark..."></textarea>
                        <div class="remark-actions">
                            <button class="remark-btn" onclick="saveProviderRemark()">Save Remark</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════
     STATUS MODAL
════════════════════════════════════ -->
<div class="stov" id="stOv" onclick="if(event.target===this)closeStatusModal()">
    <div class="stm">
        <div class="stm-hdr">
            <h3>Update Provider Status</h3>
            <button class="stm-close" onclick="closeStatusModal()">✕</button>
        </div>
        <div class="stm-body">
            <div class="stm-pname" id="stmProvName">—</div>
            <div class="stm-label">Select new status</div>
            <div class="stm-options">
                <div class="sto" id="sto-z" onclick="selectStatus('Active')">
                    <div class="sto-dot" id="dot-z"></div>
                    <div>
                        <div class="sto-label" style="color:var(--green)">Active</div>
                        <div class="sto-desc">Provider is active and visible.</div>
                    </div>
                </div>
                <div class="sto" id="sto-c" onclick="selectStatus('Greylist')">
                    <div class="sto-dot" id="dot-c"></div>
                    <div>
                        <div class="sto-label" style="color:var(--yellow)">Greylist</div>
                        <div class="sto-desc">Provider is under review.</div>
                    </div>
                </div>
                <div class="sto" id="sto-b" onclick="selectStatus('Blacklisted')">
                    <div class="sto-dot" id="dot-b"></div>
                    <div>
                        <div class="sto-label" style="color:var(--red)">Blacklisted</div>
                        <div class="sto-desc">Provider is barred until the selected date. Reason required.</div>
                    </div>
                </div>
            </div>

            <div class="bl-until-wrap" id="blUntilWrap">
                <div class="bl-until-label">Blacklist Until</div>
                <input class="bl-until-date" type="date" id="blUntilTa">
                <div class="bl-until-help">After this date expires, the status returns to Greylist.</div>
            </div>

            <div class="bl-reason-wrap" id="blReasonWrap">
                <div class="bl-reason-label">Reason for Blacklisting <span style="color:var(--red)">*</span></div>
                <select class="bl-reason-ta" id="blReasonTa">
                    <option value="">Select a reason</option>
                    <option value="Fraud / falsified records">Fraud / falsified records</option>
                    <option value="Repeated complaints">Repeated complaints</option>
                    <option value="Misconduct / unethical behavior">Misconduct / unethical behavior</option>
                    <option value="Safety / compliance violations">Safety / compliance violations</option>
                    <option value="Poor course delivery / performance">Poor course delivery / performance</option>
                </select>
            </div>

            <div class="stm-actions">
                <button class="stm-cancel" onclick="closeStatusModal()">Cancel</button>
                <button class="stm-confirm" onclick="confirmStatus()">Confirm Status</button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════
     EXPERTISE MODAL
════════════════════════════════════ -->
<div class="stov" id="expOv" onclick="if(event.target===this)closeExpertiseModal()">
    <div class="stm">
        <div class="stm-hdr">
            <h3>Update Area of Expertise</h3>
            <button class="stm-close" onclick="closeExpertiseModal()">✕</button>
        </div>
        <div class="stm-body">
            <div class="stm-pname" id="expProvName">—</div>
            <div class="stm-label">Select area of expertise</div>
            <div style="margin-bottom: 1.5rem;">
                <select class="bl-reason-ta" id="expCategorySel" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; font-size: 0.875rem;">
                    <option value="">-- Select a category --</option>
                </select>
            </div>
            <div class="stm-actions">
                <button class="stm-cancel" onclick="closeExpertiseModal()">Cancel</button>
                <button class="stm-confirm" onclick="confirmExpertise()">Update Expertise</button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════
     PARTICIPANTS MODAL
════════════════════════════════════ -->
<div class="pov" id="partOv" onclick="if(event.target===this)closeParticipantsModal()">
    <div class="pom">
        <div class="poh">
            <div>
                <h3 id="partT">Participants</h3>
                <p id="partS"></p>
            </div>
            <button class="poc" onclick="closeParticipantsModal()">✕</button>
        </div>
        <div class="pos">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input class="psi" id="partSearch" type="text" placeholder="Search participants by name, department or course..." oninput="filterParticipants()">
        </div>
        <div class="pob">
            <table class="ptbl">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Department</th><th>Course</th><th>Completion Year</th></tr>
                </thead>
                <tbody id="partBd"></tbody>
            </table>
        </div>
        <div class="pof"><span class="pi" id="pgI"></span><div class="pbs" id="pgBs"></div></div>
    </div>
</div>

<!-- ════════════════════════════════════
     TRAINER MODAL
════════════════════════════════════ -->
<div class="ov" id="trmOv" onclick="if(event.target===this)closeTrainerModal()">
    <div class="pm">
        <div class="mh">
            <div>
                <div class="mpn" id="trmN">—</div>
                <div class="mbd" id="trmStats" style="font-size:.82rem;opacity:.9">—</div>
            </div>
            <button class="mc" onclick="closeTrainerModal()">✕</button>
        </div>
        <div class="ma">
            <div class="mar" style="gap:.5rem;align-items:center">
                <span style="font-size:.75rem;color:var(--muted);font-family:'Calibri',sans-serif">Training Providers</span>
            </div>
        </div>
        <div class="mb2">
            <div id="trmProviders"></div>
        </div>
        <div class="ma">
            <div class="mar" style="gap:.5rem;align-items:center">
                <span style="font-size:.75rem;color:var(--muted);font-family:'Calibri',sans-serif">Courses Taught</span>
            </div>
        </div>
        <div class="mb2">
            <div id="trmCourses"></div>
        </div>

        <div class="ma">
            <div class="mar" style="gap:.5rem;align-items:center">
                <span style="font-size:.75rem;color:var(--muted);font-family:'Calibri',sans-serif">Remarks</span>
            </div>
        </div>
        <div class="mb2">
            <div id="trmRemarks"></div>
            <div class="remark-form" id="trmRemarkForm" style="display:<?php echo ($_SESSION['role'] === 'admin' ? 'block' : 'none'); ?>;margin-top:1rem;">
                <textarea class="remark-input" id="trmRemarkInput" rows="4" placeholder="Write a trainer remark..."></textarea>
                <div class="remark-actions">
                    <button class="remark-btn" onclick="saveTrainerRemark()">Save Remark</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════
     TRAINER MODAL
════════════════════════════════════ -->
<div class="ov" id="trainerOv" onclick="if(event.target===this)closeTrainerModal()">
    <div class="pm">
        <div class="mh">
            <div><div class="mpn" id="trainerName">—</div></div>
            <button class="mc" onclick="closeTrainerModal()">✕</button>
        </div>
        <div class="mst" id="trainerStatRow">
            <div class="msb" id="trainerProvBub">
                <div class="msb-n" id="trainerProvCount">—</div>
                <div class="msb-l">Providers</div>
            </div>
            <div class="msb" id="trainerCourseCountBub">
                <div class="msb-n" id="trainerCourseCount">—</div>
                <div class="msb-l">Courses Taught</div>
            </div>
        </div>
        <div class="ptabs">
            <button class="ptab active" id="ttab-providers" onclick="switchTrainerTab('providers', this)">Training Providers</button>
            <button class="ptab" id="ttab-courses" onclick="switchTrainerTab('courses', this)">Courses Taught</button>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="ptab" id="ttab-remarks" onclick="switchTrainerTab('remarks', this)">Remarks</button>
            <?php endif; ?>
        </div>

        <div class="ptab-panels">
            <div class="ptab-panel active" id="trainerTab-providers">
                <div class="mb2">
                    <div id="trainerProvidersC"></div>
                </div>
            </div>
            <div class="ptab-panel" id="trainerTab-courses">
                <div class="mb2">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <div id="trainerYearHeading"></div>
                        <div class="mar">
                            <span style="font-size:.75rem;color:var(--muted);font-family:'Calibri',sans-serif">Filter year:</span>
                            <select class="ysel" id="trainerYSel" onchange="renderTrainerCourses()"><option value="all">All Years</option></select>
                        </div>
                    </div>
                    <div id="trainerCoursesC"></div>
                </div>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="ptab-panel" id="trainerTab-remarks">
                <div class="mb2">
                    <div id="trainerRemarksC"></div>
                    <div class="remark-form" id="trainerRemarkForm" style="display:block;">
                        <textarea class="remark-input" id="trainerRemarkInput" rows="4" placeholder="Write a trainer remark..."></textarea>
                        <div class="remark-actions">
                            <button class="remark-btn" onclick="saveTrainerRemark()">Save Remark</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════
     UPLOAD MODAL (Admin Only)
     (included always so client-side toggles work even if session not yet persisted)
════════════════════════════ -->
<div class="uov" id="upOv" onclick="if(event.target===this)closeUpload()">
    <div class="uom">
        <div class="uoh"><h3>Upload Excel Database</h3><button class="uoc" onclick="closeUpload()">✕</button></div>
        <div class="uob">
            <div class="dz" id="dz" onclick="document.getElementById('fi2').click()" ondragover="dragOver(event)" ondragleave="dragLeave()" ondrop="dropFile(event)">
                <div class="di">📊</div>
                <div class="dt">Drop your Excel file here</div>
                <div class="ds">or <span onclick="event.stopPropagation();document.getElementById('fi2').click()">browse to upload</span></div>
                <div style="margin-top:.45rem;font-size:.71rem;color:var(--muted);font-family:'Calibri',sans-serif">.xlsx · .xls · .csv</div>
            </div>
            <input type="file" id="fi2" class="fi2" accept=".xlsx,.xls,.csv" onchange="fileSelected(event)">
            <div class="finfo" id="finfo"><span>📄</span><span id="fn"></span></div>
            <div class="uprog" id="uprog"><div class="pw"><div class="pf" id="pf"></div></div><div class="pl" id="plbl">Uploading…</div></div>
            <div class="ua"><button class="ux" onclick="closeUpload()">Cancel</button><button class="uc" onclick="performUpload()">Update Database</button></div>
        </div>
    </div>
</div>

<!-- UPLOAD PREVIEW MODAL -->
<div class="pov" id="upPreviewOv" onclick="if(event.target===this)closeUploadPreview()">
    <div class="pom">
        <div class="poh">
            <div>
                <h3 id="upPreviewTitle">Upload Preview</h3>
                <p id="upPreviewSummary"></p>
            </div>
            <button class="poc" onclick="closeUploadPreview()">✕</button>
        </div>
        <div class="pob" id="upPreviewBody" style="max-height:40vh;overflow:auto;padding:.5rem">
            <!-- Sample rows table inserted here -->
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;padding:.75rem">
            <button class="ux" onclick="closeUploadPreview()">Cancel</button>
            <button class="uc" id="confirmImportBtn" onclick="confirmImport()">Confirm Import</button>
        </div>
    </div>
</div>

<!-- LOGIN MODAL -->
<div class="lgov" id="loginOv" onclick="if(event.target===this)closeLoginModal()">
    <div class="lgm">
        <div class="lgm-hdr">
            <div class="lgm-title">Administrator Login</div>
            <button class="lgm-close" onclick="closeLoginModal()">✕</button>
        </div>
        <div class="lgm-body">
            <div class="lgm-instructions">Enter admin password to access admin features.</div>
            <input id="loginPwd" class="lgm-input" type="password" placeholder="Admin password" onkeydown="if(event.key==='Enter'){submitLoginFromModal()}" />
            <div id="loginErr" class="lgm-err" role="alert"></div>
            <div class="lgm-actions">
                <button class="ux" onclick="closeLoginModal()">Cancel</button>
                <button class="uc" onclick="submitLoginFromModal()">Log In</button>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
// Global state
let currentView = 'prov';
let sortAsc = true;
let selectedFile = null;
let allData = [];
let currentProviderDetail = null;
let currentTrainerDetail = null;
let currentProviderTab = 'courses';
let currentTrainerTab = 'providers';
let currentParticipants = [];
let participantPage = 1;
let participantSearch = '';
let pendingStatus = '';
let uploadPreviewActive = false;
let pendingExpertiseId = null;
let pendingExpertiseWhich = 1;
let allCategories = [];

function syncBodyLock() {
    const anyOpen = ['provOv', 'stOv', 'expOv', 'partOv', 'trainerOv', 'upOv'].some(id => {
        const el = document.getElementById(id);
        return el && (el.classList.contains('open') || el.style.display === 'flex');
    });
    document.body.style.overflow = anyOpen ? 'hidden' : '';
}

function hideUploadModalKeepState() {
    const up = document.getElementById('upOv');
    if (up) up.classList.remove('open');
    document.body.style.overflow = '';
}

function revealUploadModalKeepState() {
    const up = document.getElementById('upOv');
    if (up) up.classList.add('open');
    document.body.style.overflow = 'hidden';
}

const SERVER_IS_ADMIN = <?php echo json_encode($_SESSION['role'] === 'admin'); ?>;

function handleAuthButton() {
    const authBtn = document.getElementById('authHdrBtn');
    const isAdmin = SERVER_IS_ADMIN;

    if (isAdmin) {
        fetch('api/admin-logout.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        }).then(() => {
            location.reload();
        }).catch(err => {
            console.error('logout failed', err);
            showToast('⚠️ Could not log out');
        });
        return;
    }
    openLoginModal();
}

function openLoginModal() {
    const ov = document.getElementById('loginOv');
    if (!ov) return;
    ov.classList.add('open');
    setTimeout(() => {
        const inp = document.getElementById('loginPwd');
        if (inp) inp.focus();
    }, 80);
}

function closeLoginModal() {
    const ov = document.getElementById('loginOv');
    if (!ov) return;
    ov.classList.remove('open');
    const err = document.getElementById('loginErr');
    if (err) { err.textContent = ''; err.classList.remove('show'); }
    const inp = document.getElementById('loginPwd');
    if (inp) inp.value = '';
}

function submitLoginFromModal() {
    const inp = document.getElementById('loginPwd');
    const err = document.getElementById('loginErr');
    if (!inp) return;
    const password = inp.value || '';
    if (!password) {
        if (err) { err.textContent = 'Please enter the admin password'; err.classList.add('show'); }
        return;
    }

    fetch('api/admin-login.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password })
    }).then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            throw new Error(data.message || 'Could not log in');
        }
        location.reload();
    }).catch(e => {
        console.error('login failed', e);
        if (err) { err.textContent = e.message || 'Login failed'; err.classList.add('show'); }
    });
}

// Search and filter
function handleSearch() {
    loadData();
}

// View toggle
function setView(view, btn) {
    currentView = view;
    document.querySelectorAll('.vtb').forEach(btn => btn.classList.remove('active'));
    if (btn) {
        btn.classList.add('active');
    }

    const sfSel = document.getElementById('sfSel');
    sfSel.innerHTML = '';
    if (view === 'prov') {
        [['all','All'],['active','Active'],['greylist','Greylist'],['blacklisted','Blacklisted']]
            .forEach(([val,lbl]) => {
                const o = document.createElement('option');
                o.value = val;
                o.textContent = lbl;
                sfSel.appendChild(o);
            });
    } else {
        [['all','All Trainers / Speakers'],['redflag','Red Flag Only']]
            .forEach(([val,lbl]) => {
                const o = document.createElement('option');
                o.value = val;
                o.textContent = lbl;
                sfSel.appendChild(o);
            });
    }

    loadData();
}

// Sort toggle
function toggleSort() {
    sortAsc = !sortAsc;
    document.getElementById('sortLbl').textContent = sortAsc ? 'A → Z' : 'Z → A';
    loadData();
}

// Load data from server
function loadData() {
    const search = document.getElementById('si').value;
    const status = document.getElementById('sfSel').value;
    const params = new URLSearchParams({
        view: currentView,
        search: search,
        status: status,
        sort: sortAsc ? 'asc' : 'desc',
        _: String(Date.now())
    });
    
    fetch('api/get-data.php?' + params, { credentials: 'same-origin', cache: 'no-store' })
        .then(r => {
            if (!r.ok) {
                return r.json().then(payload => {
                    throw new Error(payload?.error || 'Failed to load data');
                }).catch(() => {
                    throw new Error('Failed to load data');
                });
            }
            return r.json();
        })
        .then(data => {
            if (!Array.isArray(data)) {
                throw new Error(data?.error || 'Invalid data response');
            }
            allData = data;
            renderData();
        })
        .catch(err => {
            showToast('⚠️ Error loading data');
            console.error(err);
        });
}

// Render data based on current view
function renderData() {
    const provGrid = document.getElementById('provGrid');
    const trainGrid = document.getElementById('trainGrid');
    
    if (currentView === 'prov') {
        provGrid.classList.remove('hidden');
        trainGrid.classList.add('hidden');
        renderProviders();
    } else {
        provGrid.classList.add('hidden');
        trainGrid.classList.remove('hidden');
        renderTrainers();
    }
    
    updateStats();
}

// Render providers
function renderProviders() {
    const grid = document.getElementById('provGrid');
    grid.innerHTML = '';
    
    if (allData.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;padding:2rem;text-align:center;color:var(--muted)">No training providers found</div>';
        const rl = document.getElementById('rl');
        if (rl) rl.textContent = 'Showing 0 of 0 Training Providers';
        return;
    }
    
    allData.forEach(provider => {
        const providerStatus = provider.TP_Status || '';
        const normalizedStatus = providerStatus === 'In Consideration' ? 'Greylist' : (providerStatus || 'Active');
        const statusClass = normalizedStatus.toLowerCase().replace(' ', '_');
        const statusColor = {
            'active': 'a',
            'greylist': 'c',
            'in_consideration': 'c',
            'blacklisted': 'b'
        }[statusClass] || '';
        const displayStatus = normalizedStatus || 'Active';

        const primaryAoe = provider.TP_FirstAoEDisplay || provider.TP_FirstAoE || null;
        const secondaryAoe = provider.TP_SecondAoEDisplay || provider.TP_SecondAoE || null;
        const aoeSubtitle = primaryAoe
            ? (secondaryAoe ? `${primaryAoe} | ${secondaryAoe}` : primaryAoe)
            : 'No area of expertise yet';

        const trainerNames = String(provider.trainer_names || '')
            .split('||')
            .map(x => x.trim())
            .filter(Boolean);

        const trainerRows = (trainerNames.length ? trainerNames : ['No trainers listed']).map((name, idx) => {
            const initials = name === 'No trainers listed'
                ? '—'
                : name.split(' ').filter(Boolean).slice(0, 2).map(part => part[0].toUpperCase()).join('');
            const avClass = ['av1', 'av2', 'av3', 'av4'][idx % 4];
            return `
                <div class="tp">
                    <div class="tav ${avClass}">${initials}</div>
                    <div style="flex:1;min-width:0">
                        <div class="tn">${name}</div>
                        <div class="tr2">Trainer / Speaker</div>
                    </div>
                </div>
            `;
        }).join('');
        
        const card = document.createElement('div');
        card.className = 'pc';
        card.innerHTML = `
            <div class="pc-band ${statusColor ? 'band-' + statusColor : 'band-none'}"></div>
            <div class="pc-body">
                <div class="phr">
                    <div class="plo">${provider.TP_Name.split(' ').filter(Boolean).slice(0,2).map(w => w[0]).join('').toUpperCase()}</div>
                    <div>
                        <div class="pn">${provider.TP_Name}</div>
                        <div class="pt">${aoeSubtitle}</div>
                    </div>
                </div>
                <div class="psr">
                    <span class="bdg b-${statusColor}">${displayStatus}</span>
                </div>
                <div class="pdv"></div>
                <div>
                    <div class="fl">Trainers / Speakers</div>
                    <div class="tpills">${trainerRows}</div>
                </div>
            </div>
            <div class="pc-foot">
                <button class="vb" onclick="openProviderModal(${provider.TP_ID})">View</button>
            </div>
        `;
        grid.appendChild(card);
    });

    const rl = document.getElementById('rl');
    if (rl) rl.textContent = `Showing ${allData.length} of ${allData.length} Training Providers`;
}

// Render trainers
function renderTrainers() {
    const grid = document.getElementById('trainGrid');
    grid.innerHTML = '';
    
    if (allData.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;padding:2rem;text-align:center;color:var(--muted)">No trainers found</div>';
        const rl = document.getElementById('rl');
        if (rl) rl.textContent = 'Showing 0 of 0 Trainers / Speakers';
        return;
    }
    
    allData.forEach(trainer => {
        const initials = trainer.Trainer_Name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map(part => part[0].toUpperCase())
            .join('');

        const providers = Array.isArray(trainer.providers) ? trainer.providers : [];
        const providerRows = (providers.length ? providers : [{ TP_Name: 'No providers listed', TP_Status: '' }]).map((provider, idx) => {
            const providerStatus = provider.TP_Status || '';
            const normalizedStatus = providerStatus === 'In Consideration' ? 'Greylist' : (providerStatus || 'Active');
            const statusClass = normalizedStatus.toLowerCase().replace(' ', '_');
            const statusColor = {
                'active': 'a',
                'greylist': 'c',
                'in_consideration': 'c',
                'blacklisted': 'b'
            }[statusClass] || '';
            const name = provider.TP_Name || 'N/A';
            const initials = name === 'No providers listed'
                ? '—'
                : name.split(' ').filter(Boolean).slice(0, 2).map(part => part[0].toUpperCase()).join('');

            return `
                <div class="tp tp-provider">
                    <div class="tav ${['av1','av2','av3','av4'][idx % 4]}">${initials}</div>
                    <div style="flex:1;min-width:0">
                        <div class="tn">${name}</div>
                    </div>
                    <span class="bdg b-${statusColor} tpr-badge">${normalizedStatus || 'Active'}</span>
                </div>
            `;
        }).join('');

        const card = document.createElement('div');
        card.className = 'tc2';
        card.innerHTML = `
            <div class="tc2-inner">
                <div class="tctop">
                    <div class="tavlg" style="background:var(--accent)">${initials}</div>
                    <div>
                        <div class="tfn">${trainer.Trainer_Name}</div>
                        <div class="tsp">${trainer.provider_count} providers · ${trainer.course_count} courses</div>
                    </div>
                </div>
                <div class="tcdv"></div>
                <div class="fl">Training Provider</div>
                <div class="tpills tpills-trainer">${providerRows}</div>
            </div>
            <div class="tc2-foot">
                <button class="vb" onclick="openTrainerModal(${trainer.Trainer_ID})">View</button>
                <button class="ftb${trainer.Trainer_Status ? ' flagged' : ''}" style="display:${SERVER_IS_ADMIN ? 'flex' : 'none'}" onclick="showToast('🚩 Red Flag workflow available in full mode')">🚩 Red Flag</button>
            </div>
        `;
        grid.appendChild(card);
    });

    const rl = document.getElementById('rl');
    if (rl) rl.textContent = `Showing ${allData.length} of ${allData.length} Trainers / Speakers`;
}

// Update statistics
function updateStats() {
    fetch('api/get-stats.php?_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
        .then(r => r.json())
        .then(stats => {
            document.getElementById('cntTotal').textContent = stats.total_providers || 0;
            document.getElementById('cntActive').textContent = stats.providers_active || 0;
            document.getElementById('cntGrey').textContent = stats.providers_greylist ?? stats.providers_in_consideration ?? 0;
            document.getElementById('cntBlack').textContent = stats.providers_blacklisted || 0;
        });
}

// Open provider modal
function openProviderModal(id) {
    fetch('api/get-provider.php?id=' + id + '&_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
        .then(r => {
            if (!r.ok) {
                return r.json().then(payload => {
                    throw new Error(payload?.error || 'Failed to load provider');
                }).catch(() => {
                    throw new Error('Failed to load provider');
                });
            }
            return r.json();
        })
        .then(provider => {
            currentProviderDetail = provider;
            document.getElementById('mN').textContent = provider.TP_Name;
            renderProviderSummaryBubbles();
            document.getElementById('mPa').textContent = provider.participant_count || 0;
            renderExpertiseBubble(1);
            renderExpertiseBubble(2);
            currentProviderTab = 'courses';

            const ySel = document.getElementById('ySel');
            const years = Array.from(new Set((provider.courses || [])
                .map(course => parseCourseYear(course.Completion_Date || course.Item_Date))
                .filter(Boolean)))
                .sort((a, b) => b - a);

            ySel.innerHTML = '<option value="all">All Years</option>' + years.map(year => `<option value="${year}">${year}</option>`).join('');
            ySel.value = 'all';
            renderHistory();
            renderStatusHistory();
            renderProviderRemarks();
            switchProviderTab('courses');
            
            document.getElementById('provOv').classList.add('open');
            syncBodyLock();
        })
        .catch(err => {
            showToast('⚠️ Could not load provider details');
            console.error(err);
        });
}

function openTrainerModal(id) {
    fetch('api/get-trainer.php?id=' + id + '&_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
        .then(r => {
            if (!r.ok) {
                return r.json().then(payload => {
                    throw new Error(payload?.error || 'Failed to load trainer');
                }).catch(() => {
                    throw new Error('Failed to load trainer');
                });
            }
            return r.json();
        })
        .then(trainer => {
            currentTrainerDetail = trainer;
            document.getElementById('trainerName').textContent = trainer.Trainer_Name || '—';
            document.getElementById('trainerProvCount').textContent = trainer.provider_count || 0;
            document.getElementById('trainerCourseCount').textContent = trainer.course_count || 0;

            currentTrainerTab = 'providers';
            renderTrainerProviders();
            renderTrainerCourses();
            renderTrainerRemarks();
            
            const trainerYSel = document.getElementById('trainerYSel');
            const years = Array.from(new Set((trainer.courses || [])
                .map(course => parseCourseYear(course.Completion_Date || course.Item_Date))
                .filter(Boolean)))
                .sort((a, b) => b - a);

            trainerYSel.innerHTML = '<option value="all">All Years</option>' + years.map(year => `<option value="${year}">${year}</option>`).join('');
            trainerYSel.value = 'all';
            
            switchTrainerTab('providers');
            
            document.getElementById('trainerOv').classList.add('open');
            syncBodyLock();
        })
        .catch(err => {
            showToast('⚠️ Could not load trainer details');
            console.error(err);
        });
}

function closeTrainerModal() {
    document.getElementById('trainerOv').classList.remove('open');
    syncBodyLock();
}

function switchTrainerTab(tabName, button) {
    currentTrainerTab = tabName;
    const panels = document.querySelectorAll('#trainerTab-providers, #trainerTab-courses, #trainerTab-remarks');
    panels.forEach(p => p.classList.remove('active'));
    
    const buttons = document.querySelectorAll('#ttab-providers, #ttab-courses, #ttab-remarks');
    buttons.forEach(b => b.classList.remove('active'));
    
    const panelEl = document.getElementById('trainerTab-' + tabName);
    const buttonEl = document.getElementById('ttab-' + tabName);
    if (panelEl) panelEl.classList.add('active');
    if (buttonEl) buttonEl.classList.add('active');
}

function renderTrainerProviders() {
    const container = document.getElementById('trainerProvidersC');
    if (!container || !currentTrainerDetail) return;
    
    const trainer = currentTrainerDetail;
    const courses = trainer.courses || [];
    
    if (courses.length === 0) {
        container.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No training providers listed</div>';
        return;
    }
    
    // Group courses by provider-year to track status per year
    const coursesByProviderYear = new Map();
    courses.forEach(course => {
        const dateInfo = formatCourseDate(course.Completion_Date || course.Item_Date);
        const year = dateInfo.year || 'Unknown';
        const key = `${course.TP_ID}|${year}`;
        
        if (!coursesByProviderYear.has(key)) {
            coursesByProviderYear.set(key, []);
        }
        coursesByProviderYear.get(key).push(course);
    });
    
    // Extract providers with their status for each year (use last course in year)
    const providersByYear = new Map();
    coursesByProviderYear.forEach((coursesForProvider, key) => {
        const [providerId, year] = key.split('|');
        const lastCourse = coursesForProvider[coursesForProvider.length - 1];
        
        if (!providersByYear.has(year)) providersByYear.set(year, new Map());
        
        providersByYear.get(year).set(providerId, {
            TP_ID: parseInt(providerId),
            TP_Name: lastCourse.TP_Name,
            TP_Status: lastCourse.TP_Status || 'Active'
        });
    });
    
    const years = Array.from(providersByYear.keys()).sort((a, b) => {
        if (a === 'Unknown') return 1;
        if (b === 'Unknown') return -1;
        return b - a;
    });
    
    container.innerHTML = years.map(year => {
        const providers = Array.from(providersByYear.get(year).values());
        const providerRows = providers.map(provider => {
            const status = provider.TP_Status || 'Active';
            const statusClass = status === 'Blacklisted' ? 'b-b' : status === 'Greylist' ? 'b-c' : 'b-a';
            const initials = provider.TP_Name.split(' ').filter(Boolean).slice(0, 2).map(part => part[0].toUpperCase()).join('');
            return `
                <div class="hr2" onclick="closeTrainerModal(); openProviderModal(${provider.TP_ID})" style="cursor:pointer;">
                    <div class="ht">${provider.TP_Name}</div>
                    <span class="bdg ${statusClass}">${status}</span>
                </div>
            `;
        }).join('');
        
        return `<div class="yb"><div class="yh">${year}</div><div class="yr"></div>${providerRows}</div>`;
    }).join('');
}

function renderTrainerCourses() {
    const container = document.getElementById('trainerCoursesC');
    if (!container || !currentTrainerDetail) return;
    
    const trainer = currentTrainerDetail;
    const courses = trainer.courses || [];
    const trainerYSel = document.getElementById('trainerYSel');
    const selectedYear = trainerYSel ? trainerYSel.value : 'all';
    
    if (courses.length === 0) {
        container.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No courses taught yet</div>';
        return;
    }
    
    const grouped = new Map();
    courses.forEach(course => {
        const dateInfo = formatCourseDate(course.Completion_Date || course.Item_Date);
        const year = dateInfo.year || 'Unknown';
        if (!grouped.has(year)) grouped.set(year, []);
        grouped.get(year).push({ course, dateInfo });
    });
    
    const years = Array.from(grouped.keys()).sort((a, b) => {
        if (a === 'Unknown') return 1;
        if (b === 'Unknown') return -1;
        return b - a;
    });
    
    const visibleYears = selectedYear === 'all' ? years : years.filter(year => String(year) === String(selectedYear));
    
    if (!visibleYears.length) {
        container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--muted);font-size:.87rem;font-family:\'Calibri\',sans-serif">No courses for this year.</div>';
        return;
    }
    
    container.innerHTML = visibleYears.map(year => {
        const courseRows = grouped.get(year).map(({ course, dateInfo }) => {
            const paxClass = SERVER_IS_ADMIN ? 'pax-btn admin-pax' : 'pax-btn';
            const itemId = course.Item_ID || course.Trainer_Item_ID || course.id;
            const onclick = SERVER_IS_ADMIN && itemId ? `onclick="openParticipantsModal(${itemId}, ${course.TP_ID})"` : '';
            return `
            <div class="hr2">
                <div class="ht">${course.Item_Name || 'N/A'}</div>
                <div style="display:flex;gap:1.5rem;align-items:center;flex-wrap:nowrap;">
                    <div style="flex-shrink:0;font-size:.75rem;color:var(--muted);white-space:nowrap;">${course.TP_Name || 'N/A'}</div>
                    <div style="flex-shrink:0;font-size:.75rem;color:var(--muted);display:flex;align-items:center;gap:.15rem;white-space:nowrap;"><span class="hci">📅</span><span class="hct hct-day">${dateInfo.day}</span><span class="hct hct-month">${dateInfo.month}</span></div>
                    <div style="flex-shrink:0;"><span class="${paxClass}" ${onclick}><span class="hci">👥</span><span class="hct" style="font-size:.75rem;">${course.participant_count || 0} pax</span></span></div>
                </div>
            </div>
        `;
        }).join('');
        
        return `<div class="yb"><div class="yh">${year}</div><div class="yr"></div>${courseRows}</div>`;
    }).join('');
}

// Close modals
function closeModal() {
    document.getElementById('provOv').classList.remove('open');
    syncBodyLock();
}

function renderProviderSummaryBubbles() {
    const provider = currentProviderDetail;
    if (!provider) return;

    const isAdmin = SERVER_IS_ADMIN;
    const status = provider.TP_Status || 'Active';
    const statusClass = status === 'Blacklisted' ? 'status-blacklisted' : status === 'Greylist' ? 'status-greylist' : 'status-active';

    const stBub = document.getElementById('mStBub');
    const paBub = document.getElementById('mPaBub');
    const expBub = document.getElementById('mExpBub');
    const exp2Bub = document.getElementById('mExp2Bub');

    if (stBub) {
        stBub.className = 'msb' + (isAdmin ? ' cl ' + statusClass : '');
        stBub.innerHTML = isAdmin ? `
            <div class="msb-n" id="mSt">${status || 'Active'}</div>
            <div class="msb-l">Status</div>
            <div class="msb-h ${status === 'Blacklisted' ? 'red2' : status === 'Greylist' ? 'amber' : 'green'}" id="mStHint">Click to update →</div>
        ` : `
            <div class="msb-n" id="mSt">${status || 'Active'}</div>
            <div class="msb-l">Status</div>
        `;
        stBub.onclick = isAdmin ? () => openStatusModal() : null;
    }

    if (paBub) {
        paBub.className = 'msb' + (isAdmin ? ' cl' : '');
        paBub.innerHTML = isAdmin ? `
            <div class="msb-n" id="mPa">${provider.participant_count || 0}</div>
            <div class="msb-l">Total Participants</div>
            <div class="msb-h" id="mPaHint">Click to view →</div>
        ` : `
            <div class="msb-n" id="mPa">${provider.participant_count || 0}</div>
            <div class="msb-l">Total Participants</div>
        `;
        paBub.onclick = isAdmin ? () => openParticipantsModal() : null;
    }
}

function renderExpertiseBubble(which) {
    if (!currentProviderDetail) return;
    const bubId = which === 2 ? 'mExp2Bub' : 'mExpBub';
    const displayField = which === 2 ? 'TP_SecondAoEDisplay' : 'TP_FirstAoEDisplay';
    const fallbackField = which === 2 ? 'TP_SecondAoE' : 'TP_FirstAoE';
    const currentVal = currentProviderDetail[displayField]
        || currentProviderDetail[fallbackField]
        || '—';

    const bubble = document.getElementById(bubId);
    if (!bubble) return;

    const hintHtml = SERVER_IS_ADMIN ? `<div class="msb-h">Click to update →</div>` : '';
    bubble.innerHTML = `
        <div class="msb-n">${currentVal || '—'}</div>
        <div class="msb-l">Area of Expertise</div>
        ${hintHtml}
    `;
    bubble.classList.remove('exp-open');
    if (SERVER_IS_ADMIN) {
        bubble.classList.add('cl');
    } else {
        bubble.classList.remove('cl');
    }
    
    // Make clickable for admins
    if (SERVER_IS_ADMIN) {
        bubble.onclick = () => openExpertiseModal(which);
        bubble.style.cursor = 'pointer';
    } else {
        bubble.onclick = null;
        bubble.style.cursor = 'default';
    }
}

function openStatusModal() {
    if (!currentProviderDetail) return;
    pendingStatus = currentProviderDetail.TP_Status || 'Active';
    document.getElementById('stmProvName').textContent = currentProviderDetail.TP_Name || '—';
    const reason = document.getElementById('blReasonTa');
    if (reason) reason.value = currentProviderDetail.blacklistReason || '';
    const until = document.getElementById('blUntilTa');
    if (until) {
        const today = getTodayLocalISODate();
        until.min = today;
        until.value = currentProviderDetail.TP_StatusEndDate || '';
    }
    renderStatusOptions();
    document.getElementById('stOv').classList.add('open');
    syncBodyLock();
}

function closeStatusModal() {
    document.getElementById('stOv').classList.remove('open');
    syncBodyLock();
}

function getTodayLocalISODate() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function openExpertiseModal(which) {
    if (!currentProviderDetail) return;
    pendingExpertiseId = currentProviderDetail.TP_ID;
    pendingExpertiseWhich = which;
    
    document.getElementById('expProvName').textContent = currentProviderDetail.TP_Name || '—';
    
    // Load categories if not already loaded
    if (allCategories.length === 0) {
        fetch('api/get-categories.php', { credentials: 'same-origin', cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                allCategories = data.categories || [];
                populateExpertiseSelect();
            })
            .catch(err => {
                console.error('Failed to load categories:', err);
                showToast('⚠️ Could not load categories');
            });
    } else {
        populateExpertiseSelect();
    }
    
    document.getElementById('expOv').classList.add('open');
    syncBodyLock();
}

function populateExpertiseSelect() {
    const sel = document.getElementById('expCategorySel');
    const displayField = pendingExpertiseWhich === 2 ? 'TP_SecondAoEDisplay' : 'TP_FirstAoEDisplay';
    const fallbackField = pendingExpertiseWhich === 2 ? 'TP_SecondAoE' : 'TP_FirstAoE';
    const otherField = pendingExpertiseWhich === 2 ? 'TP_FirstAoE' : 'TP_SecondAoE';
    const currentVal = currentProviderDetail[displayField] || currentProviderDetail[fallbackField] || '';
    const otherVal = (currentProviderDetail[otherField] || '').trim();
    
    sel.innerHTML = '<option value="">-- Select a category --</option>';
    
    allCategories.forEach(cat => {
        // Prevent selecting the same category in both AoE slots.
        if (otherVal && cat === otherVal && cat !== currentVal) {
            return;
        }
        const opt = document.createElement('option');
        opt.value = cat;
        opt.textContent = cat;
        if (cat === currentVal) opt.selected = true;
        sel.appendChild(opt);
    });
}

function closeExpertiseModal() {
    document.getElementById('expOv').classList.remove('open');
    syncBodyLock();
}

function confirmExpertise() {
    if (!pendingExpertiseId) return;
    
    const selectedCategory = document.getElementById('expCategorySel').value;
    const otherField = pendingExpertiseWhich === 2 ? 'TP_FirstAoE' : 'TP_SecondAoE';
    const otherSelected = (currentProviderDetail?.[otherField] || '').trim();

    if (selectedCategory && otherSelected && selectedCategory === otherSelected) {
        showToast('⚠️ First and Second Area of Expertise must be different');
        return;
    }
    
    const formData = new FormData();
    formData.append('id', pendingExpertiseId);
    formData.append('which', pendingExpertiseWhich);
    formData.append('category', selectedCategory);
    
    fetch('api/update-expertise.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(r => {
            if (!r.ok) return r.json().then(data => { throw new Error(data.error || 'Update failed'); });
            return r.json();
        })
        .then(data => {
            showToast('✓ Area of Expertise updated');
            closeExpertiseModal();
            // Reload provider detail to reflect changes
            if (currentProviderDetail && currentProviderDetail.TP_ID) {
                openProviderModal(currentProviderDetail.TP_ID);
            }
            // Refresh provider cards immediately (same pattern as status updates).
            loadData();
        })
        .catch(err => {
            console.error(err);
            showToast('⚠️ Failed to update Area of Expertise: ' + err.message);
        });
}

function selectStatus(status) {
    pendingStatus = status || 'Active';
    renderStatusOptions();
}

function renderStatusOptions() {
    const statuses = [
        ['Active', 'sto-z', 'dot-z'],
        ['Greylist', 'sto-c', 'dot-c'],
        ['Blacklisted', 'sto-b', 'dot-b']
    ];

    statuses.forEach(([status, rowId, dotId]) => {
        const row = document.getElementById(rowId);
        const dot = document.getElementById(dotId);
        if (!row || !dot) return;
        const active = pendingStatus === status;
        row.classList.toggle('active', active);
        dot.classList.toggle('active', active);
    });

    const wrap = document.getElementById('blReasonWrap');
    const untilWrap = document.getElementById('blUntilWrap');
    if (wrap) {
        const show = pendingStatus === 'Blacklisted';
        wrap.classList.toggle('show', show);
        // Fallback inline style to ensure visibility if CSS is overridden
        wrap.style.display = show ? 'block' : 'none';
    }
    if (untilWrap) {
        const show = pendingStatus === 'Blacklisted';
        untilWrap.classList.toggle('show', show);
        untilWrap.style.display = show ? 'block' : 'none';
    }
}

function confirmStatus() {
    if (!currentProviderDetail) return;
    const reason = document.getElementById('blReasonTa')?.value.trim() || '';
    const blacklistUntil = document.getElementById('blUntilTa')?.value || '';
    if (pendingStatus === 'Blacklisted' && !reason) {
        showToast('⚠️ Please provide a reason for blacklisting.');
        document.getElementById('blReasonTa')?.focus();
        return;
    }
    if (pendingStatus === 'Blacklisted' && !blacklistUntil) {
        showToast('⚠️ Please choose an end date for the blacklist.');
        document.getElementById('blUntilTa')?.focus();
        return;
    }
    if (pendingStatus === 'Blacklisted' && blacklistUntil) {
        const today = getTodayLocalISODate();
        if (blacklistUntil < today) {
            showToast('⚠️ Blacklist end date cannot be earlier than today.');
            document.getElementById('blUntilTa')?.focus();
            return;
        }
    }

    fetch('api/update-provider-status.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentProviderDetail.TP_ID, status: pendingStatus, reason, blacklist_until: blacklistUntil })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) throw new Error(data.error || 'Unable to update status');
        currentProviderDetail.TP_Status = pendingStatus;
        currentProviderDetail.TP_StatusEndDate = pendingStatus === 'Blacklisted' ? blacklistUntil : '';
        currentProviderDetail.blacklistReason = pendingStatus === 'Blacklisted' ? reason : '';

        if (!Array.isArray(currentProviderDetail.status_history)) {
            currentProviderDetail.status_history = [];
        }
        currentProviderDetail.status_history.unshift({
            TP_Status_ID: 'new',
            TP_Status: pendingStatus,
            TP_StatusRaw: pendingStatus,
            TP_StatusDisplay: pendingStatus || 'Active',
            TP_StatusReasoning: pendingStatus === 'Blacklisted' ? reason : null,
            TP_StatusStartDate: new Date().toISOString().slice(0, 10),
            TP_StatusEndDate: pendingStatus === 'Blacklisted' ? blacklistUntil : null,
            TP_StatusEffective: pendingStatus,
            TP_StatusExpired: false
        });
        closeStatusModal();
        renderProviderSummaryBubbles();
        renderStatusHistory();
        loadData();
        updateStats();
        showToast(`✓ Status updated to "${pendingStatus || 'Active'}"`);
    })
    .catch(err => {
        console.error(err);
        showToast('⚠️ Could not update provider status');
    });
}

function openParticipantsModal(itemId, providerId) {
    // Use provided providerId or fall back to currentProviderDetail
    const actualProviderId = providerId || (currentProviderDetail ? currentProviderDetail.TP_ID : null);
    
    if (!actualProviderId) {
        showToast('⚠️ Provider not selected');
        return;
    }
    
    // Resolve against the current page URL so both / and /index.php loads work.
    const url = new URL('api/get-provider-participants.php', window.location.href);
    url.searchParams.set('id', actualProviderId);
    if (itemId) url.searchParams.set('item_id', String(itemId));
    url.searchParams.set('_', String(Date.now()));
    console.log('Fetching participants URL:', url.toString());
    fetch(url.toString(), { credentials: 'same-origin', cache: 'no-store' })
        .then(r => {
            if (!r.ok) {
                console.error('Participants fetch failed', r.status, r.url);
                return r.json().then(payload => { throw new Error(payload?.error || 'Failed to load participants'); }).catch(() => { throw new Error('Failed to load participants'); });
            }
            return r.json();
        })
        .then(payload => {
            currentParticipants = Array.isArray(payload.participants) ? payload.participants : [];
            participantPage = 1;
            participantSearch = '';
            document.getElementById('partT').textContent = `Participants — ${payload.provider_name || currentProviderDetail?.TP_Name || '—'}` + (payload.course_name ? ` — ${payload.course_name}` : '');
            document.getElementById('partS').textContent = `${payload.participant_count || currentParticipants.length || 0} total registered participants`;
            document.getElementById('partSearch').value = '';
            renderParticipants();
            document.getElementById('partOv').classList.add('open');
            syncBodyLock();
        })
        .catch(err => {
            console.error(err);
            showToast('⚠️ Could not load participant list');
        });
}

function closeParticipantsModal() {
    document.getElementById('partOv').classList.remove('open');
    syncBodyLock();
}

function switchProviderTab(tab, btn) {
    currentProviderTab = tab;
    document.querySelectorAll('.ptab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.ptab-panel').forEach(el => el.classList.remove('active'));

    const button = btn || document.getElementById(`ptab-${tab}`);
    if (button) {
        button.classList.add('active');
    }

    const panel = document.getElementById(`providerTab-${tab}`);
    if (panel) {
        panel.classList.add('active');
    }
}

function formatDisplayDate(dateValue, includeTime = false) {
    if (!dateValue) return '—';
    const parsed = new Date(dateValue);
    if (Number.isNaN(parsed.getTime())) return String(dateValue);

    const options = includeTime
        ? { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }
        : { year: 'numeric', month: 'short', day: 'numeric' };

    return (includeTime ? parsed.toLocaleString('en-GB', options) : parsed.toLocaleDateString('en-GB', options)).replace(',', '');
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function filterParticipants() {
    participantSearch = document.getElementById('partSearch')?.value.toLowerCase().trim() || '';
    participantPage = 1;
    renderParticipants();
}

function renderParticipants() {
    const body = document.getElementById('partBd');
    const pager = document.getElementById('pgBs');
    const info = document.getElementById('pgI');
    if (!body || !pager || !info) return;

    const filtered = currentParticipants.filter(row => {
        if (!participantSearch) return true;
        return String(row.Participant_Name || '').toLowerCase().includes(participantSearch)
            || String(row.Participant_Department || '').toLowerCase().includes(participantSearch)
            || String(row.Course_Name || '').toLowerCase().includes(participantSearch);
    });

    const pageSize = 10;
    const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
    participantPage = Math.min(participantPage, pageCount);
    const start = (participantPage - 1) * pageSize;
    const pageRows = filtered.slice(start, start + pageSize);

    body.innerHTML = pageRows.length ? pageRows.map((row, idx) => `
        <tr>
            <td>${start + idx + 1}</td>
            <td><strong>${row.Participant_Name || '—'}</strong></td>
            <td><span class="dtag">${row.Participant_Department || '—'}</span></td>
            <td>${row.Course_Name || '—'}</td>
            <td>${row.Completion_Year || '—'}</td>
        </tr>
    `).join('') : `<tr><td colspan="5" style="text-align:center;padding:1.4rem;color:var(--muted)">No participants found</td></tr>`;

    info.textContent = filtered.length ? `Showing ${start + 1}-${Math.min(start + pageSize, filtered.length)} of ${filtered.length}` : 'Showing 0 of 0';
    pager.innerHTML = '';
    const buttons = [];
    buttons.push({ label: '‹', page: Math.max(1, participantPage - 1), disabled: participantPage === 1 });
    for (let i = 1; i <= pageCount; i++) buttons.push({ label: String(i), page: i, active: i === participantPage });
    buttons.push({ label: '›', page: Math.min(pageCount, participantPage + 1), disabled: participantPage === pageCount });
    buttons.forEach(btn => {
        const el = document.createElement('button');
        el.className = 'pb' + (btn.active ? ' active' : '');
        el.textContent = btn.label;
        if (btn.disabled) el.disabled = true;
        if (!btn.disabled) el.onclick = () => { participantPage = btn.page; renderParticipants(); };
        pager.appendChild(el);
    });
}

function parseCourseYear(dateValue) {
    if (!dateValue) return null;
    const parsed = new Date(dateValue);
    return Number.isNaN(parsed.getTime()) ? null : parsed.getFullYear();
}

function formatCourseDate(dateValue) {
    if (!dateValue) return { year: null, day: 'N/A', month: '' };
    const parsed = new Date(dateValue);
    if (Number.isNaN(parsed.getTime())) {
        return { year: null, day: 'N/A', month: String(dateValue) };
    }

    const months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    return {
        year: parsed.getFullYear(),
        day: parsed.getDate(),
        month: months[parsed.getMonth()]
    };
}

// Upload functions
function dragOver(e) {
    e.preventDefault();
    document.getElementById('dz').classList.add('dg');
}

function dragLeave() {
    document.getElementById('dz').classList.remove('dg');
}

function dropFile(e) {
    e.preventDefault();
    dragLeave();
    const f = e.dataTransfer.files[0];
    if (f) selectFile(f.name, f);
}

function fileSelected(e) {
    const f = e.target.files[0];
    if (f) selectFile(f.name, f);
}

function selectFile(name, file) {
    selectedFile = file;
    document.getElementById('finfo').style.display = 'flex';
    document.getElementById('fn').textContent = name;
}

function openUpload() {
    document.getElementById('upOv').classList.add('open');
    document.body.style.overflow = 'hidden';
    resetUpload();
}

function closeUpload() {
    document.getElementById('upOv').classList.remove('open');
    document.body.style.overflow = '';
}

function resetUpload() {
    document.getElementById('finfo').style.display = 'none';
    document.getElementById('uprog').style.display = 'none';
    document.getElementById('pf').style.width = '0';
    document.getElementById('fi2').value = '';
    selectedFile = null;
}

function performUpload() {
    if (!selectedFile) {
        showToast('⚠️ Select a file first');
        return;
    }

    // Send preview request first
    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('preview', '1');

    const prog = document.getElementById('uprog');
    prog.style.display = 'block';
    document.getElementById('pf').style.width = '0';
    document.getElementById('plbl').textContent = 'Preparing preview…';

    fetch('api/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        if (result.success && result.preview) {
            // show preview modal
            showUploadPreview(result);
            document.getElementById('plbl').textContent = 'Preview ready';
        } else if (result.success) {
            // fallback: no preview, treat as success
            document.getElementById('pf').style.width = '100%';
            document.getElementById('plbl').textContent = 'Upload complete ✓';
            setTimeout(() => {
                closeUpload();
                showToast('✅ Database updated successfully!');
                loadData();
                updateStats();
            }, 700);
        } else {
            showToast('❌ ' + (result.message || 'Preview failed'));
            prog.style.display = 'none';
        }
    })
    .catch(err => {
        showToast('❌ Upload error: ' + err.message);
        prog.style.display = 'none';
    });
}

function showUploadPreview(result) {
    const body = document.getElementById('upPreviewBody');
    const summary = document.getElementById('upPreviewSummary');
    body.innerHTML = '';
    summary.textContent = `${result.counts.total_rows} rows — ${result.counts.unique_providers} providers · ${result.counts.unique_trainers} trainers · ${result.counts.unique_courses} courses`;

    const rows = result.sample_rows || [];
    if (rows.length) {
    const tbl = document.createElement('table');
    // allow table to size to content so horizontal scroll can appear
    tbl.style.width = 'max-content';
    tbl.style.borderCollapse = 'collapse';
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        ['TP_Name','Trainer_Name','Item_Name','Item_Category','Participant_Name','Participant_Department','Completion_Date'].forEach(h => {
            const th = document.createElement('th'); th.textContent = h; th.style.borderBottom = '1px solid #ddd'; th.style.padding = '.25rem'; headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        tbl.appendChild(thead);
        const tb = document.createElement('tbody');
        rows.forEach(r => {
            const tr = document.createElement('tr');
            ['TP_Name','Trainer_Name','Item_Name','Item_Category','Participant_Name','Participant_Department','Completion_Date'].forEach(k => {
                const td = document.createElement('td'); td.textContent = r[k] || ''; td.style.padding = '.25rem'; tr.appendChild(td);
            });
            tb.appendChild(tr);
        });
        tbl.appendChild(tb);
        body.appendChild(tbl);

        // enable horizontal scrolling with mouse wheel when vertical scroll isn't available
        // or when user holds Shift (standard behaviour), improving discoverability
        body.addEventListener('wheel', function(e) {
            try {
                const canScrollVertically = body.scrollHeight > body.clientHeight + 1;
                const canScrollHorizontally = body.scrollWidth > body.clientWidth + 1;
                if (!canScrollHorizontally) return; // nothing to do

                // If there's no vertical overflow, map vertical wheel to horizontal scroll
                if (!canScrollVertically || e.shiftKey) {
                    e.preventDefault();
                    body.scrollLeft += e.deltaY;
                }
                // otherwise allow normal vertical scrolling
            } catch (err) {
                console.error('Preview wheel handler error', err);
            }
        }, { passive: false });
    } else {
        body.innerHTML = '<div style="padding:1rem;color:var(--muted)">No sample rows available</div>';
    }

    // show any missing rows info
    if (result.rows_with_missing_required && result.rows_with_missing_required.length) {
        const warn = document.createElement('div');
        warn.style.color = 'var(--red)';
        warn.style.marginTop = '.5rem';
        warn.textContent = 'Rows with missing required fields: ' + result.rows_with_missing_required.join(', ');
        body.appendChild(warn);
    }

    // Hide the upload modal behind and show preview on top
    hideUploadModalKeepState();
    uploadPreviewActive = true;
    document.getElementById('upPreviewOv').classList.add('open');
}

function closeUploadPreview(reopen = true) {
    document.getElementById('upPreviewOv').classList.remove('open');
    // hide progress if still visible
    document.getElementById('uprog').style.display = 'none';
    uploadPreviewActive = false;
    if (reopen) {
        // return to upload modal preserving selected file
        revealUploadModalKeepState();
    }
}

function confirmImport() {
    // perform final upload (no preview flag)
    if (!selectedFile) return closeUploadPreview();
    const formData = new FormData();
    formData.append('file', selectedFile);

    const prog = document.getElementById('uprog');
    const fill = document.getElementById('pf');
    const lbl = document.getElementById('plbl');
    prog.style.display = 'block';
    fill.style.width = '0';
    lbl.textContent = 'Uploading…';

    fetch('api/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            fill.style.width = '100%';
            lbl.textContent = 'Upload complete ✓';
            setTimeout(() => {
                // close preview without reopening upload
                closeUploadPreview(false);
                closeUpload();
                showToast('✅ Database updated successfully!');
                loadData();
                updateStats();
            }, 700);
        } else {
            showToast('❌ ' + result.message);
            prog.style.display = 'none';
        }
    })
    .catch(err => {
        showToast('❌ Upload error: ' + err.message);
        prog.style.display = 'none';
    });
}

function downloadExport() {
    showToast('📥 Download feature coming soon');
}

// Toast notifications
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3200);
}

function renderHistory() {
    const histC = document.getElementById('histC');
    const ySel = document.getElementById('ySel');
    if (!histC || !currentProviderDetail) return;

    const courses = Array.isArray(currentProviderDetail.courses) ? currentProviderDetail.courses : [];
    histC.innerHTML = '';

    if (!courses.length) {
        histC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No courses yet</div>';
        return;
    }

    const selectedYear = ySel ? ySel.value : 'all';
    const grouped = new Map();

    courses.forEach(course => {
        const dateInfo = formatCourseDate(course.Completion_Date || course.Item_Date);
        const year = dateInfo.year || 'Unknown';
        if (!grouped.has(year)) grouped.set(year, []);
        grouped.get(year).push({ course, dateInfo });
    });

    const years = Array.from(grouped.keys()).sort((a, b) => {
        if (a === 'Unknown') return 1;
        if (b === 'Unknown') return -1;
        return b - a;
    });
    const visibleYears = selectedYear === 'all' ? years : years.filter(year => String(year) === String(selectedYear));

    if (!visibleYears.length) {
        histC.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--muted);font-size:.87rem;font-family:\'Calibri\',sans-serif">No courses for this year.</div>';
        return;
    }

    visibleYears.forEach(year => {
        const block = document.createElement('div');
        block.className = 'yb';

        const courseRows = grouped.get(year).map(({ course, dateInfo }) => {
            const paxClass = SERVER_IS_ADMIN ? 'pax-btn admin-pax' : 'pax-btn';
            const onclick = SERVER_IS_ADMIN && course.Item_ID ? `onclick="openParticipantsModal(${course.Item_ID})"` : '';
            return `
            <div class="hr2">
                <div class="ht">${course.Item_Name || 'N/A'}</div>
                <div class="hch">
                    <div class="hc hc-date"><span class="hci">📅</span><span class="hct hct-day">${dateInfo.day}</span><span class="hct hct-month">${dateInfo.month}</span></div>
                    <div class="hc hc-pax"><span class="${paxClass}" ${onclick}><span class="hci">👥</span><span class="hct">${course.participant_count || 0} pax</span></span></div>
                </div>
            </div>
        `;
        }).join('');

        block.innerHTML = `<div class="yh">${year}</div><div class="yr"></div>${courseRows}`;
        histC.appendChild(block);
    });
}

function renderStatusHistory() {
    const histC = document.getElementById('statusHistC');
    if (!histC || !currentProviderDetail) return;

    const history = Array.isArray(currentProviderDetail.status_history) ? currentProviderDetail.status_history : [];
    if (!history.length) {
        histC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No status history yet</div>';
        return;
    }

    histC.innerHTML = history.map(row => {
        const rawStatus = row.TP_StatusDisplay || row.TP_Status || '';
        const effective = row.TP_StatusEffective || rawStatus || '';
        const displayStatus = rawStatus || 'Active';
        const badgeClass = effective === 'Blacklisted' ? 'b-b' : effective === 'Greylist' ? 'b-c' : 'b-a';
        const startDate = formatDisplayDate(row.TP_StatusStartDate);
        const endDate = row.TP_StatusEndDate ? ` → ${formatDisplayDate(row.TP_StatusEndDate)}` : '';
        const expired = row.TP_StatusExpired ? '<div class="status-note">Expired</div>' : '';
        const reason = row.TP_StatusReasoning ? `<div class="status-note">Reason: ${escapeHtml(String(row.TP_StatusReasoning))}</div>` : '';
        return `
            <div class="status-card">
                <div class="status-top">
                    <span class="bdg ${badgeClass}">${escapeHtml(displayStatus)}</span>
                    <span class="status-date">${startDate}${endDate}</span>
                </div>
                ${expired}
                ${reason}
            </div>
        `;
    }).join('');
}

function renderProviderRemarks() {
    const remarksC = document.getElementById('provRemarksC');
    if (!remarksC || !currentProviderDetail) return;

    const remarks = Array.isArray(currentProviderDetail.remarks) ? currentProviderDetail.remarks : [];
    if (!remarks.length) {
        remarksC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No remarks yet</div>';
        return;
    }

    remarksC.innerHTML = remarks.map(row => `
        <div class="remark-card">
            <div class="remark-meta">${formatDisplayDate(row.Remark_Date, true)}</div>
            <div class="remark-text">${escapeHtml(String(row.Remark_Text || '—'))}</div>
        </div>
    `).join('');
}

function saveProviderRemark() {
    if (!currentProviderDetail) return;
    const input = document.getElementById('provRemarkInput');
    const remark = input?.value.trim() || '';
    if (!remark) {
        showToast('⚠️ Please write a remark first');
        input?.focus();
        return;
    }

    fetch('api/save-remark.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ entity: 'provider', id: currentProviderDetail.TP_ID, remark })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) throw new Error(data.error || 'Unable to save remark');
        if (!Array.isArray(currentProviderDetail.remarks)) {
            currentProviderDetail.remarks = [];
        }
        currentProviderDetail.remarks.unshift({
            Remark_Text: remark,
            Remark_Date: new Date().toISOString().slice(0, 19).replace('T', ' ')
        });
        input.value = '';
        renderProviderRemarks();
        showToast('✓ Remark saved');
    })
    .catch(err => {
        console.error(err);
        showToast('⚠️ Could not save remark');
    });
}

function renderTrainerRemarks() {
    const remarksC = document.getElementById('trainerRemarksC');
    if (!remarksC || !currentTrainerDetail) return;

    const remarks = Array.isArray(currentTrainerDetail.remarks) ? currentTrainerDetail.remarks : [];
    if (!remarks.length) {
        remarksC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No remarks yet</div>';
        return;
    }

    remarksC.innerHTML = remarks.map(row => `
        <div class="remark-card">
            <div class="remark-meta">${formatDisplayDate(row.Remark_Date, true)}</div>
            <div class="remark-text">${escapeHtml(String(row.Remark_Text || '—'))}</div>
        </div>
    `).join('');
}

function saveTrainerRemark() {
    if (!currentTrainerDetail) return;
    const input = document.getElementById('trainerRemarkInput');
    const remark = input?.value.trim() || '';
    if (!remark) {
        showToast('⚠️ Please write a remark first');
        input?.focus();
        return;
    }

    fetch('api/save-remark.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ entity: 'trainer', id: currentTrainerDetail.Trainer_ID, remark })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) throw new Error(data.error || 'Unable to save remark');
        if (!Array.isArray(currentTrainerDetail.remarks)) {
            currentTrainerDetail.remarks = [];
        }
        currentTrainerDetail.remarks.unshift({
            Remark_Text: remark,
            Remark_Date: new Date().toISOString().slice(0, 19).replace('T', ' ')
        });
        input.value = '';
        renderTrainerRemarks();
        showToast('✓ Remark saved');
    })
    .catch(err => {
        console.error(err);
        showToast('⚠️ Could not save remark');
    });
}

// Initialize on load
window.addEventListener('DOMContentLoaded', () => {
    // Show admin controls if server-side role is admin
    try {
        const upBtn = document.getElementById('upHdrBtn');
        const dlBtn = document.getElementById('dlBtn');
        const authBtn = document.getElementById('authHdrBtn');
        if (SERVER_IS_ADMIN) {
            if (upBtn) upBtn.style.display = 'inline-flex';
            if (dlBtn) dlBtn.style.display = 'flex';
            if (authBtn) authBtn.textContent = 'Log Out';
        } else {
            if (upBtn) upBtn.style.display = 'none';
            if (dlBtn) dlBtn.style.display = 'none';
            if (authBtn) authBtn.textContent = 'Log In';
        }
    } catch (e) { console.error(e); }

    loadData();
    updateStats();
});
</script>

</body>
</html>
