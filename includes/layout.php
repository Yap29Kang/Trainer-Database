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
    <title><?php echo $page_title ?? 'SEB Trainer Dashboard'; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- HEADER -->
<header>
    <div class="hdr-top">
        <div class="logo">Trainer<span> Dashboard</span></div>
        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
            <button class="hdr-btn" id="authHdrBtn" onclick="handleAuthButton()">
                <?php echo ($_SESSION['role'] === 'admin' ? 'Log Out' : 'Log In'); ?>
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
    <div class="hdr-upload-wrap">
        <button class="hdr-btn" id="upHdrBtn" onclick="openUpload()" style="<?php echo ($_SESSION['role'] === 'admin' ? 'display:inline-flex;' : 'display:none;'); ?>">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
            Manage Database
        </button>
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
        <div class="trainer-flag-reason" style="width:132px;">
            <button type="button" class="trainer-flag-reason-btn" id="sfBtn" onclick="toggleStatusFilterMenu()">All</button>
            <div class="trainer-flag-reason-menu" id="sfMenu"></div>
        </div>
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
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="ptab" id="ptab-summary" onclick="switchProviderTab('summary', this)">Summary</button>
            <?php endif; ?>
            <button class="ptab active" id="ptab-courses" onclick="switchProviderTab('courses', this)">Course History</button>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="ptab" id="ptab-status" onclick="switchProviderTab('status', this)">Status History</button>
            <button class="ptab" id="ptab-remarks" onclick="switchProviderTab('remarks', this)">Remarks</button>
            <?php endif; ?>
        </div>

        <div class="ptab-panels">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="ptab-panel" id="providerTab-summary">
                <div class="mb2">
                    <div id="providerSummaryC"></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="ptab-panel active" id="providerTab-courses">
                <div class="mb2">
                    <div class="provider-history-bar">
                        <div id="yearHeading"></div>
                        <div class="provider-history-actions">
                            <div class="mar provider-history-filter">
                                <span style="font-size:.75rem;color:var(--muted);font-family:'Calibri',sans-serif">Filter year:</span>
                                <select class="ysel" id="ySel" onchange="renderHistory()"><option value="all">All Years</option></select>
                            </div>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <button type="button" class="dl-btn provider-download-btn" onclick="downloadProviderCourses()">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                Download
                            </button>
                            <?php endif; ?>
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
                <div class="trainer-flag-reason">
                    <button type="button" class="trainer-flag-reason-btn" id="blReasonBtn" onclick="toggleBlacklistReasonMenu()">Select a reason</button>
                    <div class="trainer-flag-reason-menu" id="blReasonMenu">
                        <button type="button" class="trainer-flag-reason-item" onclick="chooseBlacklistReason('Performance quality')">Performance quality</button>
                        <button type="button" class="trainer-flag-reason-item" onclick="chooseBlacklistReason('Safety & compliance')">Safety & compliance</button>
                        <button type="button" class="trainer-flag-reason-item" onclick="chooseBlacklistReason('Fraud & misconduct')">Fraud & misconduct</button>
                    </div>
                </div>
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
            <div class="pos-controls">
                <input class="psi" id="partSearch" type="text" placeholder="Search participants by name, department or course..." oninput="filterParticipants()">
                <button class="pdl" type="button" onclick="downloadParticipants()">Download</button>
            </div>
        </div>
        <div class="pob">
            <table class="ptbl">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Department</th><th>Courses</th></tr>
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
            <button class="ptab" id="ttab-status" onclick="switchTrainerTab('status', this)">Status History</button>
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
                    <div class="trainer-history-bar">
                        <div id="trainerYearHeading"></div>
                        <div class="trainer-history-actions">
                            <div class="mar trainer-history-filter">
                                <span style="font-size:.75rem;color:var(--muted);font-family:'Calibri',sans-serif">Filter year:</span>
                                <select class="ysel" id="trainerYSel" onchange="renderTrainerCourses()"><option value="all">All Years</option></select>
                            </div>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <button type="button" class="dl-btn trainer-download-btn" onclick="downloadTrainerCourses()">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                Download
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="trainerCoursesC"></div>
                </div>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="ptab-panel" id="trainerTab-status">
                <div class="mb2">
                    <div id="trainerStatusHistC"></div>
                </div>
            </div>
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
     TRAINER RED FLAG MODAL
════════════════════════════════════ -->
<div class="stov" id="trainerFlagOv" onclick="if(event.target===this)closeTrainerFlagModal()">
    <div class="stm">
        <div class="stm-hdr">
            <h3 id="trainerFlagTitle">Red Flag Trainer</h3>
            <button class="stm-close" onclick="closeTrainerFlagModal()">✕</button>
        </div>
        <div class="stm-body">
            <div class="stm-pname" id="trainerFlagName">—</div>
            <div id="trainerFlagMessage" style="font-size:.9rem;line-height:1.5;color:var(--muted);margin-bottom:1rem;"></div>
            <div id="trainerFlagReasonWrap" style="margin-bottom:1rem;">
                <div class="stm-label">Reason for Red Flag</div>
                <div class="trainer-flag-reason">
                    <button type="button" class="trainer-flag-reason-btn" id="trainerFlagReasonBtn" onclick="toggleTrainerFlagReasonMenu()">Select a reason</button>
                    <div class="trainer-flag-reason-menu" id="trainerFlagReasonMenu">
                        <button type="button" class="trainer-flag-reason-item" onclick="chooseTrainerFlagReason('Unprofessional conduct', 'Unprofessional conduct - Behavioural issues, misconduct, or complaints from participants.')">Unprofessional conduct - Behavioural issues, misconduct, or complaints from participants.</button>
                        <button type="button" class="trainer-flag-reason-item" onclick="chooseTrainerFlagReason('Poor training quality', 'Poor training quality - Below-standard delivery, outdated content, or low feedback scores.')">Poor training quality - Below-standard delivery, outdated content, or low feedback scores.</button>
                        <button type="button" class="trainer-flag-reason-item" onclick="chooseTrainerFlagReason('Compliance or legal concern', 'Compliance or legal concern - Regulatory breach, credential issues, or ongoing legal matter.')">Compliance or legal concern - Regulatory breach, credential issues, or ongoing legal matter.</button>
                        <button type="button" class="trainer-flag-reason-item" onclick="chooseTrainerFlagReason('Reliability issues', 'Reliability issues - Repeated no-shows, late cancellations, or session disruptions.')">Reliability issues - Repeated no-shows, late cancellations, or session disruptions.</button>
                    </div>
                </div>
            </div>
            <div class="stm-actions">
                <button class="stm-cancel" onclick="closeTrainerFlagModal()">Cancel</button>
                <button class="stm-confirm" id="trainerFlagSubmitBtn" onclick="submitTrainerRedFlag()">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════
     UPLOAD MODAL (Admin Only)
     (included always so client-side toggles work even if session not yet persisted)
════════════════════════════ -->
<div class="uov" id="upOv" onclick="if(event.target===this)closeUpload()">
    <div class="uom" style="max-width: 520px; width: 100%;">
        <!-- Header -->
        <div class="uoh" style="background: var(--blue); color: #fff; padding: 1rem 1.45rem; border-radius: 14px 14px 0 0; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-family: 'Calibri', sans-serif; font-weight: 700; font-size: 1.05rem;">
                <span style="font-size: 1.2rem;">📊</span>
                <span>Manage Database</span>
            </div>
            <button class="uoc" onclick="closeUpload()" style="background: rgba(255,255,255,0.15); border: none; border-radius: 6px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #fff;">✕</button>
        </div>

        <!-- Tabs -->
        <div style="display: flex; background: var(--cream); border-bottom: 1px solid var(--border);">
            <button class="u-tab-btn active" id="utab-upload" onclick="switchUploadTab('upload')">
                📄 Upload new file
            </button>
            <button class="u-tab-btn" id="utab-history" onclick="switchUploadTab('history')">
                ⏳ Upload history <span id="upHistBadge" style="background: var(--blue); color: white; border-radius: 12px; padding: 1px 6px; font-size: 0.72rem; margin-left: 2px; font-weight: 700;">0</span>
            </button>
        </div>

        <!-- Tab Content: Upload -->
        <div class="uob" id="upContent-upload">
            <div class="dz" id="dz" onclick="document.getElementById('fi2').click()" ondragover="dragOver(event)" ondragleave="dragLeave()" ondrop="dropFile(event)" style="border: 2px dashed var(--border); border-radius: 10px; padding: 2.5rem 1.4rem; text-align: center; cursor: pointer; transition: all 0.2s; background: var(--card); margin-bottom: 1rem;">
                <div class="di" style="font-size: 2.2rem; margin-bottom: 0.55rem; color: var(--blue);">📁</div>
                <div class="dt" style="font-family: 'Calibri', sans-serif; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.22rem; color: var(--ink);">Drop your Excel file here</div>
                <div class="ds" style="font-size: 0.8rem; color: var(--muted); font-family: 'Calibri', sans-serif;">or <span style="color: var(--blue); font-weight: 700; text-decoration: underline;">click to browse</span></div>
                <div style="margin-top: 0.45rem; font-size: 0.72rem; color: var(--muted); font-family: 'Calibri', sans-serif;">.xlsx · .xls · .csv</div>
            </div>
            <input type="file" id="fi2" class="fi2" accept=".xlsx,.xls,.csv" onchange="fileSelected(event)" style="display: none;">
            
            <div class="finfo" id="finfo" style="display: none; margin-bottom: 1rem; padding: 0.5rem 0.75rem; background: var(--gbg); border: 1px solid var(--gbd); border-radius: 7px; font-size: 0.85rem; color: var(--green); align-items: center; gap: 0.38rem; font-family: 'Calibri', sans-serif;">
                <span>📄</span>
                <span id="fn" style="font-weight: 700; word-break: break-all;"></span>
            </div>



            <div class="uprog" id="uprog" style="display: none; margin-bottom: 1rem;">
                <div class="pw" style="height: 5px; background: var(--cream); border-radius: 4px; overflow: hidden; margin-bottom: 0.38rem;">
                    <div class="pf" id="pf" style="height: 100%; background: var(--blue); border-radius: 4px; width: 0; transition: width 0.22s;"></div>
                </div>
                <div class="pl" id="plbl" style="font-size: 0.75rem; color: var(--muted); text-align: center; font-family: 'Calibri', sans-serif;">Uploading…</div>
            </div>

            <div class="ua" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <button class="ux" onclick="closeUpload()">Cancel</button>
                <button class="uc" onclick="performUpload()">📤 Upload file</button>
            </div>
        </div>

        <!-- Tab Content: History -->
        <div class="uob" id="upContent-history" style="display: none;">
            <div id="uploadHistoryList" style="max-height: 280px; overflow-y: auto; margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; padding-right: 4px; min-height: 120px;">
                <!-- History items populated dynamically -->
            </div>
            
            <div class="ua" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <button class="ux" onclick="closeUpload()">Cancel</button>
                <button class="uc" onclick="closeUpload()">✓ Done</button>
            </div>
        </div>
    </div>
</div>

<!-- REMOVE CONFIRM MODAL -->
<div class="uov" id="removeConfirmOv" style="z-index:1100;" onclick="if(event.target===this)closeRemoveConfirm()">
    <div class="uom" style="max-width:420px;width:100%;padding:0;overflow:hidden;border-radius:12px;background:var(--card);box-shadow:0 10px 30px rgba(0,0,0,0.22);">
        <div style="padding:1.5rem 1.5rem 0;">
            <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.9rem;">
                <div style="width:36px;height:36px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#ef4444" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </div>
                <div style="font-family:'Calibri',sans-serif;font-weight:700;font-size:1.05rem;color:var(--ink);">Remove upload data?</div>
            </div>
            <p style="font-size:0.875rem;color:var(--muted);font-family:'Calibri',sans-serif;line-height:1.55;margin:0 0 0.25rem;">All enrollment records imported from</p>
            <p id="removeConfirmFilename" style="font-size:0.875rem;font-weight:700;color:var(--ink);font-family:'Calibri',sans-serif;margin:0 0 0.75rem;word-break:break-all;"></p>
            <p style="font-size:0.875rem;color:var(--muted);font-family:'Calibri',sans-serif;line-height:1.55;margin:0 0 1.25rem;">will be permanently deleted. <strong style="color:var(--ink);">This cannot be undone.</strong></p>
        </div>
        <div style="display:flex;gap:0.6rem;padding:0 1.5rem 1.5rem;">
            <button class="ux" style="flex:1;justify-content:center;" onclick="closeRemoveConfirm()">Cancel</button>
            <button id="removeConfirmBtn" onclick="confirmRemoveUpload()" style="flex:1;justify-content:center;background:#ef4444;color:#fff;border:none;border-radius:7px;font-family:'Calibri',sans-serif;font-weight:700;font-size:0.92rem;padding:0.6rem 1rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                Yes, remove
            </button>
        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="uov" id="successOv" style="z-index:900" onclick="if(event.target===this)closeSuccess()">
    <div class="uom" style="max-width: 480px; padding: 0; overflow: hidden; border-radius: 12px; background: var(--card); text-align: left; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="background: var(--blue-lt); padding: 2.5rem 1.5rem 2rem; text-align: center;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: var(--card); border: 1.5px solid var(--blue); color: var(--blue); margin-bottom: 1.25rem; box-shadow: 0 4px 10px rgba(0,128,198,0.15);">
                <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <h3 style="margin: 0; color: var(--blue-dk); font-size: 1.35rem; font-weight: 700; font-family: 'Calibri', sans-serif;">Upload successful</h3>
        </div>
        <div style="padding: 1.5rem;">
            <div style="background: var(--paper); border: 1px solid var(--border); border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <div style="font-size: 0.95rem; color: var(--ink); font-family: 'Calibri', sans-serif;"><strong id="successFileName" style="font-weight: 700;"></strong> <span style="color: var(--muted);" id="successFileSize"></span></div>
            </div>
            <div style="background: var(--paper); border: 1px solid var(--border); border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <div style="font-size: 0.95rem; color: var(--ink); font-family: 'Calibri', sans-serif;" id="successCounts"></div>
            </div>
            <div style="background: var(--paper); border: 1px solid var(--border); border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted);"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <div style="font-size: 0.95rem; color: var(--ink); font-family: 'Calibri', sans-serif;"><span style="color: var(--muted);">Processed in </span><strong id="successTime" style="font-weight: 700;"></strong> <span style="color: var(--muted);" id="successDate"></span></div>
            </div>
            <p style="color: var(--muted); margin: 0 0 1.5rem; font-size: 0.9rem; text-align: center; line-height: 1.5; font-family: 'Calibri', sans-serif;">The database has been updated. Changes are reflected immediately across all provider cards.</p>
            <div style="display: flex; gap: 0.75rem;">
                <button class="uc" style="flex: 1; justify-content: center; padding: 0.75rem; background: var(--card); color: var(--blue); border: 1px solid var(--border); font-size: 0.95rem; display: flex; gap: 0.5rem; align-items: center; border-radius: 7px; font-family: 'Calibri', sans-serif; font-weight: 700; cursor: pointer;" onclick="closeSuccess()">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Done
                </button>
            </div>
        </div>
    </div>
</div>



<!-- ════════════════════════════════════
     COMPLAINT MODAL
════════════════════════════════════ -->
<div class="uov" id="complaintOv" onclick="if(event.target===this)closeComplaintModal()">
    <div class="uom" style="max-width: 800px; width: 100%;">
        <!-- Header -->
        <div class="uoh" style="background: var(--blue); color: #fff; padding: 1rem 1.45rem; border-radius: 14px 14px 0 0; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-family: 'Calibri', sans-serif; font-weight: 700; font-size: 1.05rem;">
                <span style="font-size: 1.2rem;">🚩</span>
                <span>Complaints</span>
            </div>
            <button class="uoc" onclick="closeComplaintModal()" style="background: rgba(255,255,255,0.15); border: none; border-radius: 6px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #fff;">✕</button>
        </div>

        <!-- Tabs -->
        <div style="display: flex; background: var(--cream); border-bottom: 1px solid var(--border);">
            <button class="u-tab-btn active" id="ctab-new" onclick="switchComplaintTab('new')">
                New complaint
            </button>
            <button class="u-tab-btn" id="ctab-update" onclick="switchComplaintTab('update')">
                Update status
            </button>
        </div>

        <!-- Tab Content: New Complaint -->
        <div class="uob" id="compContent-new">
            <form id="newComplaintForm" onsubmit="submitNewComplaint(event)">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label class="stm-label">Date of Complaint *</label>
                        <input type="date" id="compDate" class="si" style="width:100%;margin-top:0.25rem" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label class="stm-label">Priority *</label>
                        <select id="compPriority" class="si" style="width:100%;margin-top:0.25rem" required>
                            <option value="">Select Priority</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="stm-label">Employee Name *</label>
                        <input type="text" id="compEmpName" class="si" style="width:100%;margin-top:0.25rem" required>
                    </div>
                    <div>
                        <label class="stm-label">Employee ID *</label>
                        <input type="text" id="compEmpId" class="si" style="width:100%;margin-top:0.25rem" required>
                    </div>
                    <div>
                        <label class="stm-label">Department *</label>
                        <div class="dept-select-wrap" style="position:relative;margin-top:0.25rem;">
                            <button type="button" id="compDeptBtn" class="si dept-select-btn" style="width:100%" onclick="toggleDeptMenu('compDeptMenu','compDeptBtn')">
                                <span id="compDeptLabel" class="dept-select-placeholder">Select Department</span>
                            </button>
                            <input type="hidden" id="compDept">
                            <div id="compDeptMenu" class="trainer-flag-reason-menu" style="width:100%;"></div>
                        </div>
                    </div>
                    <div>
                        <label class="stm-label">LearnOps *</label>
                        <select id="compLearnOps" class="si" style="width:100%;margin-top:0.25rem" required>
                            <option value="">Select LearnOps</option>
                            <option value="Nur Suzyla">Nur Suzyla</option>
                            <option value="Felicia">Felicia</option>
                        </select>
                    </div>
                    <div>
                        <label class="stm-label">Training Provider *</label>
                        <div style="position:relative;">
                            <input type="text" id="compTpSearch" class="si" style="width:100%;margin-top:0.25rem" placeholder="Type to search..." autocomplete="off" required oninput="filterCompTp('compTpSearch', 'compTpDropdown', 'compTpId')">
                            <input type="hidden" id="compTpId" required>
                            <div id="compTpDropdown" class="trainer-flag-reason-menu" style="width:100%;margin-top:2px;"></div>
                        </div>
                    </div>
                    <div>
                        <label class="stm-label">Complaint Category *</label>
                        <select id="compCategory" class="si" style="width:100%;margin-top:0.25rem" required>
                            <option value="">Select Category</option>
                            <option value="Performance Quality">Performance Quality</option>
                            <option value="Safety & Compliance">Safety & Compliance</option>
                            <option value="Fraud & Misconduct">Fraud & Misconduct</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:1rem;">
                    <label class="stm-label">Complaint Summary *</label>
                    <textarea id="compSummary" class="remark-input" rows="3" style="width:100%;margin-top:0.25rem" required></textarea>
                </div>
                <div class="ua" style="display: flex; gap: 0.5rem; justify-content:flex-end;">
                    <button type="button" class="ux" onclick="closeComplaintModal()">Cancel</button>
                    <button type="submit" class="uc">Submit Complaint</button>
                </div>
            </form>
        </div>

        <!-- Tab Content: Update status -->
        <div class="uob" id="compContent-update" style="display: none;">
            <div id="compListSection">
                <div style="display:flex; gap:0.5rem; margin-bottom:1rem; align-items:center;">
                    <input type="text" id="compSearchInput" class="si" placeholder="Search complaints..." style="flex:0 1 620px" oninput="fetchComplaints()">
                    <button type="button" class="dl-btn" style="display:inline-flex;margin-left:auto;" onclick="downloadComplaints()">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Download
                    </button>
                </div>
                <div id="compListContainer" style="max-height: 400px; overflow-y: auto;">
                    <!-- Complaints list inserted here -->
                </div>
            </div>

            <!-- Edit Complaint Form (Hidden initially) -->
            <div id="compEditSection" style="display:none;">
                <div style="display:flex;align-items:center;margin-bottom:1rem;gap:0.5rem;">
                    <button type="button" class="ux" onclick="showComplaintList()" style="padding:4px 8px;">← Back</button>
                    <h4 style="margin:0;font-family:'Calibri',sans-serif;">Editing Case: <span id="editCompCaseId"></span></h4>
                </div>
                <form id="editComplaintForm" onsubmit="submitEditComplaint(event)" style="display: flex; flex-direction: column; max-height: 60vh;">
                    <div style="overflow-y: auto; padding-right: 0.5rem; flex: 1;">
                        <input type="hidden" id="editCompCaseIdHidden">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                            <div>
                                <label class="stm-label">Date of Complaint *</label>
                                <input type="date" id="editCompDate" class="si" style="width:100%;margin-top:0.25rem" max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div>
                                <label class="stm-label">Priority *</label>
                                <select id="editCompPriority" class="si" style="width:100%;margin-top:0.25rem" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                            <div>
                                <label class="stm-label">Employee Name *</label>
                                <input type="text" id="editCompEmpName" class="si" style="width:100%;margin-top:0.25rem" required>
                            </div>
                            <div>
                                <label class="stm-label">Employee ID *</label>
                                <input type="text" id="editCompEmpId" class="si" style="width:100%;margin-top:0.25rem" required>
                            </div>
                            <div>
                                <label class="stm-label">Department *</label>
                                <div class="dept-select-wrap" style="position:relative;margin-top:0.25rem;">
                                    <button type="button" id="editCompDeptBtn" class="si dept-select-btn" style="width:100%" onclick="toggleDeptMenu('editCompDeptMenu','editCompDeptBtn')">
                                        <span id="editCompDeptLabel" class="dept-select-placeholder">Select Department</span>
                                    </button>
                                    <input type="hidden" id="editCompDept">
                                    <div id="editCompDeptMenu" class="trainer-flag-reason-menu" style="width:100%;"></div>
                                </div>
                            </div>
                            <div>
                                <label class="stm-label">LearnOps *</label>
                                <select id="editCompLearnOps" class="si" style="width:100%;margin-top:0.25rem" required>
                                    <option value="Nur Suzyla">Nur Suzyla</option>
                                    <option value="Felicia">Felicia</option>
                                </select>
                            </div>
                            <div>
                                <label class="stm-label">Training Provider *</label>
                                <div style="position:relative;">
                                    <input type="text" id="editCompTpSearch" class="si" style="width:100%;margin-top:0.25rem" autocomplete="off" required oninput="filterCompTp('editCompTpSearch', 'editCompTpDropdown', 'editCompTpId')">
                                    <input type="hidden" id="editCompTpId" required>
                                    <div id="editCompTpDropdown" class="trainer-flag-reason-menu" style="width:100%;margin-top:2px;"></div>
                                </div>
                            </div>
                            <div>
                                <label class="stm-label">Complaint Category *</label>
                                <select id="editCompCategory" class="si" style="width:100%;margin-top:0.25rem" required>
                                    <option value="Performance Quality">Performance Quality</option>
                                    <option value="Safety & Compliance">Safety & Compliance</option>
                                    <option value="Fraud & Misconduct">Fraud & Misconduct</option>
                                </select>
                            </div>
                            <div style="grid-column: span 2;">
                                <label class="stm-label">Complaint Summary *</label>
                                <textarea id="editCompSummary" class="remark-input" rows="2" style="width:100%;margin-top:0.25rem" required></textarea>
                            </div>

                            <!-- Update Status Specific Fields -->
                            <div>
                                <label class="stm-label">Status *</label>
                                <select id="editCompStatus" class="si" style="width:100%;margin-top:0.25rem" required>
                                    <option value="Open">Open</option>
                                    <option value="Under Review">Under Review</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="stm-label">LDCM Decision *</label>
                                <select id="editCompDecision" class="si" style="width:100%;margin-top:0.25rem" required>
                                    <option value="No Action">No Action</option>
                                    <option value="LDCM Decision">LDCM Decision</option>
                                    <option value="Blacklist">Blacklist</option>
                                </select>
                            </div>
                            <div>
                                <label class="stm-label">Decision Date *</label>
                                <input type="date" id="editCompDecisionDate" class="si" style="width:100%;margin-top:0.25rem" required>
                            </div>
                            <div style="grid-column: span 2;">
                                <label class="stm-label">Description (Remarks)</label>
                                <textarea id="editCompRemarks" class="remark-input" rows="2" style="width:100%;margin-top:0.25rem"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="ua" style="display: flex; gap: 0.5rem; justify-content:flex-end; padding-top: 1rem; border-top: 1px solid var(--border); margin-top: 0.5rem; flex-shrink: 0;">
                        <button type="button" class="ux" onclick="showComplaintList()">Cancel</button>
                        <button type="submit" class="uc">Save Changes</button>
                    </div>
                </form>
            </div>
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
let currentParticipantsProviderId = null;
let currentParticipantsItemId = null;
let participantPage = 1;
let participantSearch = '';
const expandedParticipants = new Set();
let currentListPages = { prov: 1, train: 1 };
const providerDetailCache = new Map();
const providerDetailInflight = new Map();
const trainerDetailCache = new Map();
const trainerDetailInflight = new Map();
const listDataCache = new Map();
const listDataInflight = new Map();
const participantListCache = new Map();
const participantListInflight = new Map();
let pendingStatus = '';
let pendingBlacklistReason = '';
let currentStatusFilter = 'all';

let pendingExpertiseId = null;
let pendingExpertiseWhich = 1;
let allCategories = [];
const MAX_UPLOAD_BYTES = 32 * 1024 * 1024;
const PREVIEW_SIZE_LIMIT_BYTES = 4 * 1024 * 1024;
const LIST_PAGE_SIZE = 9;

function formatBytes(size) {
    if (!Number.isFinite(size) || size < 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let idx = 0;
    let value = size;
    while (value >= 1024 && idx < units.length - 1) {
        value /= 1024;
        idx++;
    }
    return `${value.toFixed(idx === 0 ? 0 : 1)} ${units[idx]}`;
}

function syncBodyLock() {
    const anyOpen = ['provOv', 'stOv', 'expOv', 'partOv', 'trainerOv', 'trainerFlagOv', 'upOv'].some(id => {
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
    currentListPages[currentView] = 1;
    loadData();
}

function getStatusFilterOptions(view) {
    if (view === 'prov') {
        return [
            { value: 'all', label: 'All', buttonLabel: 'All' },
            { value: 'active', label: 'Active' },
            { value: 'greylist', label: 'Greylist' },
            { value: 'blacklisted', label: 'Blacklisted' }
        ];
    }

    return [
        { value: 'all', label: 'All Trainers / Speakers', buttonLabel: 'All' },
        { value: 'redflag', label: 'Red Flag Only', buttonLabel: 'Red Flag' }
    ];
}

function updateStatusFilterMenu() {
    const btn = document.getElementById('sfBtn');
    const menu = document.getElementById('sfMenu');
    if (!btn || !menu) return;

    const options = getStatusFilterOptions(currentView);
    const activeOption = options.find(option => option.value === currentStatusFilter) || options[0];
    currentStatusFilter = activeOption.value;
    btn.textContent = activeOption.buttonLabel || activeOption.label;
    menu.innerHTML = options.map(option => `
        <button type="button" class="trainer-flag-reason-item" onclick="chooseStatusFilter('${option.value}')">${option.label}</button>
    `).join('');
}

function filterListData(data, view, status) {
    const rows = Array.isArray(data) ? data.slice() : [];
    const normalizedStatus = status || 'all';

    if (view === 'prov') {
        if (normalizedStatus === 'all') return rows;
        const statusMap = {
            active: 'Active',
            blank: 'Active',
            approved: 'Active',
            consideration: 'Greylist',
            greylist: 'Greylist',
            blacklisted: 'Blacklisted'
        };
        const targetStatus = statusMap[normalizedStatus] || normalizedStatus;
        return rows.filter(row => (row.TP_Status || '') === targetStatus);
    }

    if (normalizedStatus === 'redflag') {
        return rows.filter(row => !!row.Trainer_StatusActive);
    }

    return rows;
}

function toggleStatusFilterMenu() {
    const menu = document.getElementById('sfMenu');
    if (!menu) return;
    menu.classList.toggle('open');
}

function chooseStatusFilter(value) {
    currentStatusFilter = value || 'all';
    const menu = document.getElementById('sfMenu');
    if (menu) menu.classList.remove('open');
    updateStatusFilterMenu();
    currentListPages[currentView] = 1;
    loadData();
}

// View toggle
function setView(view, btn) {
    currentView = view;
    document.querySelectorAll('.vtb').forEach(btn => btn.classList.remove('active'));
    if (btn) {
        btn.classList.add('active');
    }

    currentStatusFilter = 'all';
    updateStatusFilterMenu();

    currentListPages[view] = 1;

    loadData();
}

// Sort toggle
function toggleSort() {
    sortAsc = !sortAsc;
    document.getElementById('sortLbl').textContent = sortAsc ? 'A → Z' : 'Z → A';
    currentListPages[currentView] = 1;
    loadData();
}

function getListCacheKey(view, search, sort) {
    return `${view}|${search}|${sort}`;
}

function getCurrentListQuery() {
    return {
        view: currentView,
        search: document.getElementById('si')?.value || '',
        status: currentStatusFilter || 'all',
        sort: sortAsc ? 'asc' : 'desc'
    };
}

function renderListCacheIfAvailable(query) {
    const cacheKey = getListCacheKey(query.view, query.search, query.sort);
    if (!listDataCache.has(cacheKey)) return false;
    allData = filterListData(listDataCache.get(cacheKey), query.view, query.status);
    renderData();
    return true;
}

function prefetchViewData(view, search, sort) {
    const params = new URLSearchParams({
        view,
        search: search || '',
        sort: sort || 'asc',
        _: 'prefetch'
    });
    const cacheKey = getListCacheKey(view, search || '', sort || 'asc');
    if (listDataCache.has(cacheKey) || listDataInflight.has(cacheKey)) return;

    const promise = fetch('api/get-data.php?' + params, { credentials: 'same-origin', cache: 'no-store' })
        .then(r => r.ok ? r.json() : r.json().then(payload => { throw new Error(payload?.error || 'Failed to load data'); }).catch(() => { throw new Error('Failed to load data'); }))
        .then(data => {
            if (Array.isArray(data)) {
                listDataCache.set(cacheKey, data);
            }
            return data;
        })
        .finally(() => {
            listDataInflight.delete(cacheKey);
        });

    listDataInflight.set(cacheKey, promise);
}

// Load data from server
function loadData() {
    const search = document.getElementById('si').value;
    const query = {
        view: currentView,
        search,
        status: currentStatusFilter || 'all',
        sort: sortAsc ? 'asc' : 'desc'
    };
    const cacheKey = getListCacheKey(query.view, query.search, query.sort);

    if (renderListCacheIfAvailable(query)) {
        const oppositeView = currentView === 'prov' ? 'train' : 'prov';
        if (query.search || query.sort) {
            prefetchViewData(oppositeView, query.search, query.sort);
        }
        return;
    }

    const params = new URLSearchParams({
        view: query.view,
        search: query.search,
        sort: query.sort,
        _: String(Date.now())
    });

    if (listDataInflight.has(cacheKey)) {
        listDataInflight.get(cacheKey)
            .then(data => {
                if (Array.isArray(data)) {
                    allData = data;
                    renderData();
                }
            })
            .catch(err => {
                showToast('⚠️ Error loading data');
                console.error(err);
            });
        return;
    }
    
    const request = fetch('api/get-data.php?' + params, { credentials: 'same-origin', cache: 'no-store' })
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
            listDataCache.set(cacheKey, data);
            allData = filterListData(data, query.view, query.status);
            renderData();
            const oppositeView = currentView === 'prov' ? 'train' : 'prov';
            if (query.search || query.sort) {
                prefetchViewData(oppositeView, query.search, query.sort);
            }
        })
        .finally(() => {
            listDataInflight.delete(cacheKey);
        })
        .catch(err => {
            showToast('⚠️ Error loading data');
            console.error(err);
        });

    listDataInflight.set(cacheKey, request);
}

// Render data based on current view
function renderData() {
    const provGrid = document.getElementById('provGrid');
    const trainGrid = document.getElementById('trainGrid');
    const pager = document.getElementById('listPager');
    
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

function renderListPagination(totalItems) {
    const pager = document.getElementById('listPager');
    if (!pager) return;

    pager.innerHTML = '';

    const pageCount = Math.max(1, Math.ceil(totalItems / LIST_PAGE_SIZE));
    const page = Math.min(currentListPages[currentView] || 1, pageCount);
    currentListPages[currentView] = page;

    if (pageCount <= 1) return;

    const makeButton = (label, targetPage, disabled = false, active = false) => {
        const button = document.createElement('button');
        button.className = 'lp-btn' + (active ? ' active' : '');
        button.textContent = label;
        button.disabled = disabled;
        if (!disabled) {
            button.onclick = () => {
                currentListPages[currentView] = targetPage;
                renderData();
            };
        }
        return button;
    };

    pager.appendChild(makeButton('‹', Math.max(1, page - 1), page === 1));

    const visiblePages = [];
    const addVisiblePage = (candidate) => {
        if (candidate >= 1 && candidate <= pageCount && !visiblePages.includes(candidate)) {
            visiblePages.push(candidate);
        }
    };

    if (pageCount <= 7) {
        for (let i = 1; i <= pageCount; i++) addVisiblePage(i);
    } else {
        addVisiblePage(1);
        addVisiblePage(2);
        addVisiblePage(page - 1);
        addVisiblePage(page);
        addVisiblePage(page + 1);
        addVisiblePage(pageCount - 1);
        addVisiblePage(pageCount);
        visiblePages.sort((a, b) => a - b);
    }

    let lastPage = null;
    visiblePages.forEach(pageNumber => {
        if (lastPage !== null && pageNumber - lastPage > 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'lp-ellipsis';
            ellipsis.textContent = '…';
            pager.appendChild(ellipsis);
        }
        pager.appendChild(makeButton(String(pageNumber), pageNumber, false, pageNumber === page));
        lastPage = pageNumber;
    });

    pager.appendChild(makeButton('›', Math.min(pageCount, page + 1), page === pageCount));
}

// Render providers
function renderProviders() {
    const grid = document.getElementById('provGrid');
    grid.innerHTML = '';
    
    if (allData.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;padding:2rem;text-align:center;color:var(--muted)">No training providers found</div>';
        const rl = document.getElementById('rl');
        if (rl) rl.textContent = 'Showing 0 of 0 Training Providers';
        renderListPagination(0);
        return;
    }

    const pageCount = Math.max(1, Math.ceil(allData.length / LIST_PAGE_SIZE));
    currentListPages.prov = Math.min(currentListPages.prov || 1, pageCount);
    const start = (currentListPages.prov - 1) * LIST_PAGE_SIZE;
    const pageData = allData.slice(start, start + LIST_PAGE_SIZE);
    
    pageData.forEach(provider => {
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
                    <div class="fl">Trainers / Speakers</div>
                    <div class="tpills">${trainerRows}</div>
                </div>
            </div>
            <div class="pc-foot">
                <button class="vb" onpointerenter="prefetchProviderModal(${provider.TP_ID})" onfocus="prefetchProviderModal(${provider.TP_ID})" onclick="openProviderModal(${provider.TP_ID})">View</button>
            </div>
        `;
        grid.appendChild(card);
    });

    const rl = document.getElementById('rl');
    if (rl) rl.textContent = `Showing ${start + 1}-${Math.min(start + LIST_PAGE_SIZE, allData.length)} of ${allData.length} Training Providers`;
    renderListPagination(allData.length);
}

// Render trainers
function renderTrainers() {
    const grid = document.getElementById('trainGrid');
    grid.innerHTML = '';
    
    if (allData.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;padding:2rem;text-align:center;color:var(--muted)">No trainers found</div>';
        const rl = document.getElementById('rl');
        if (rl) rl.textContent = 'Showing 0 of 0 Trainers / Speakers';
        renderListPagination(0);
        return;
    }

    const pageCount = Math.max(1, Math.ceil(allData.length / LIST_PAGE_SIZE));
    currentListPages.train = Math.min(currentListPages.train || 1, pageCount);
    const start = (currentListPages.train - 1) * LIST_PAGE_SIZE;
    const pageData = allData.slice(start, start + LIST_PAGE_SIZE);
    
    pageData.forEach(trainer => {
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
                    <div class="tfn-wrap">
                        <div class="tfn-row">
                            <div class="tfn">${trainer.Trainer_Name}</div>
                            ${trainer.Trainer_StatusActive ? '<span class="bdg b-rf tfn-badge">Red Flag</span>' : ''}
                        </div>
                        <div class="tsp">${trainer.provider_count} providers · ${trainer.course_count} courses</div>
                    </div>
                </div>
                <div class="tcdv"></div>
                <div class="fl">Training Provider</div>
                <div class="tpills tpills-trainer">${providerRows}</div>
            </div>
            <div class="tc2-foot">
                <button class="vb" onpointerenter="prefetchTrainerModal(${trainer.Trainer_ID})" onfocus="prefetchTrainerModal(${trainer.Trainer_ID})" onclick="openTrainerModal(${trainer.Trainer_ID})">View</button>
                <button class="ftb${trainer.Trainer_StatusActive ? ' flagged' : ''}" style="display:${SERVER_IS_ADMIN ? 'flex' : 'none'}" onclick="openTrainerRedFlagModal(${trainer.Trainer_ID}, ${trainer.Trainer_StatusActive ? 'true' : 'false'})">${trainer.Trainer_StatusActive ? 'Remove Red Flag' : 'Red Flag'}</button>
            </div>
        `;
        grid.appendChild(card);
    });

    const rl = document.getElementById('rl');
    if (rl) rl.textContent = `Showing ${start + 1}-${Math.min(start + LIST_PAGE_SIZE, allData.length)} of ${allData.length} Trainers / Speakers`;
    renderListPagination(allData.length);
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

function getProviderCacheKey(id) {
    return String(id);
}

function getParticipantsCacheKey(providerId, itemId) {
    return `${providerId || '0'}:${itemId || 'all'}`;
}

function loadProviderDetail(id) {
    const cacheKey = getProviderCacheKey(id);
    if (providerDetailCache.has(cacheKey)) {
        return Promise.resolve(providerDetailCache.get(cacheKey));
    }
    if (providerDetailInflight.has(cacheKey)) {
        return providerDetailInflight.get(cacheKey);
    }

    const promise = fetch('api/get-provider.php?id=' + encodeURIComponent(id) + '&_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
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
            providerDetailCache.set(cacheKey, provider);
            return provider;
        })
        .finally(() => {
            providerDetailInflight.delete(cacheKey);
        });

    providerDetailInflight.set(cacheKey, promise);
    return promise;
}

function getTrainerCacheKey(id) {
    return String(id);
}

function loadTrainerDetail(id) {
    const cacheKey = getTrainerCacheKey(id);
    if (trainerDetailCache.has(cacheKey)) {
        return Promise.resolve(trainerDetailCache.get(cacheKey));
    }
    if (trainerDetailInflight.has(cacheKey)) {
        return trainerDetailInflight.get(cacheKey);
    }

    const promise = fetch('api/get-trainer.php?id=' + encodeURIComponent(id) + '&_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
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
            trainerDetailCache.set(cacheKey, trainer);
            return trainer;
        })
        .finally(() => {
            trainerDetailInflight.delete(cacheKey);
        });

    trainerDetailInflight.set(cacheKey, promise);
    return promise;
}

function prefetchTrainerModal(id) {
    if (!id) return;
    loadTrainerDetail(id).catch(() => {});
}

function prefetchProviderModal(id) {
    if (!id) return;
    loadProviderDetail(id).catch(() => {});
}

function loadParticipantsData(providerId, itemId) {
    const cacheKey = getParticipantsCacheKey(providerId, itemId);
    if (participantListCache.has(cacheKey)) {
        return Promise.resolve(participantListCache.get(cacheKey));
    }
    if (participantListInflight.has(cacheKey)) {
        return participantListInflight.get(cacheKey);
    }

    const url = new URL('api/get-provider-participants.php', window.location.href);
    url.searchParams.set('id', providerId);
    if (itemId) url.searchParams.set('item_id', String(itemId));
    url.searchParams.set('_', String(Date.now()));

    const promise = fetch(url.toString(), { credentials: 'same-origin', cache: 'no-store' })
        .then(r => {
            if (!r.ok) {
                return r.json().then(payload => {
                    throw new Error(payload?.error || 'Failed to load participants');
                }).catch(() => {
                    throw new Error('Failed to load participants');
                });
            }
            return r.json();
        })
        .then(payload => {
            participantListCache.set(cacheKey, payload);
            return payload;
        })
        .finally(() => {
            participantListInflight.delete(cacheKey);
        });

    participantListInflight.set(cacheKey, promise);
    return promise;
}

function setProviderModalLoading() {
    const title = document.getElementById('mN');
    const participantCount = document.getElementById('mPa');
    const yearHeading = document.getElementById('yearHeading');
    const ySel = document.getElementById('ySel');
    const histC = document.getElementById('histC');
    const summaryC = document.getElementById('providerSummaryC');
    const statusHistC = document.getElementById('statusHistC');
    const provRemarksC = document.getElementById('provRemarksC');

    if (title) title.textContent = 'Loading provider details...';
    if (participantCount) participantCount.textContent = '—';
    if (yearHeading) yearHeading.textContent = 'Loading...';
    if (ySel) ySel.innerHTML = '<option value="all">All Years</option>';
    if (histC) histC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">Loading provider details...</div>';
    if (summaryC) summaryC.innerHTML = '';
    if (statusHistC) statusHistC.innerHTML = '';
    if (provRemarksC) provRemarksC.innerHTML = '';
}

function renderProviderModal(provider) {
    currentProviderDetail = provider;
    document.getElementById('mN').textContent = provider.TP_Name;
    renderProviderSummaryBubbles();
    document.getElementById('mPa').textContent = provider.participant_count || 0;
    const yearHeading = document.getElementById('yearHeading');
    if (yearHeading) yearHeading.textContent = '';
    renderExpertiseBubble(1);
    renderExpertiseBubble(2);
    currentProviderTab = SERVER_IS_ADMIN ? 'summary' : 'courses';

    const ySel = document.getElementById('ySel');
    const years = Array.from(new Set((provider.courses || [])
        .map(course => parseCourseYear(course.Completion_Date || course.Item_Date))
        .filter(Boolean)))
        .sort((a, b) => b - a);

    ySel.innerHTML = '<option value="all">All Years</option>' + years.map(year => `<option value="${year}">${year}</option>`).join('');
    ySel.value = 'all';
    renderProviderSummaryTab();
    renderHistory();
    renderStatusHistory();
    renderProviderRemarks();
    switchProviderTab(currentProviderTab);
}

function setTrainerModalLoading() {
    const name = document.getElementById('trainerName');
    const provCount = document.getElementById('trainerProvCount');
    const courseCount = document.getElementById('trainerCourseCount');
    const providersC = document.getElementById('trainerProvidersC');
    const coursesC = document.getElementById('trainerCoursesC');
    const remarksC = document.getElementById('trainerRemarksC');
    const trainerYSel = document.getElementById('trainerYSel');

    if (name) name.textContent = 'Loading trainer details...';
    if (provCount) provCount.textContent = '—';
    if (courseCount) courseCount.textContent = '—';
    if (providersC) providersC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">Loading trainer details...</div>';
    if (coursesC) coursesC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">Loading trainer details...</div>';
    if (remarksC) remarksC.innerHTML = '';
    if (trainerYSel) trainerYSel.innerHTML = '<option value="all">All Years</option>';
}

function renderTrainerModal(trainer) {
    currentTrainerDetail = trainer;
    document.getElementById('trainerName').textContent = trainer.Trainer_Name || '—';
    document.getElementById('trainerProvCount').textContent = trainer.provider_count || 0;
    document.getElementById('trainerCourseCount').textContent = trainer.course_count || 0;
    const trainerYearHeading = document.getElementById('trainerYearHeading');
    if (trainerYearHeading) trainerYearHeading.textContent = '';

    currentTrainerTab = 'providers';
    renderTrainerProviders();
    renderTrainerCourses();
    renderTrainerStatusHistory();
    renderTrainerRemarks();

    const trainerYSel = document.getElementById('trainerYSel');
    const years = Array.from(new Set((trainer.courses || [])
        .map(course => parseCourseYear(course.Completion_Date || course.Item_Date))
        .filter(Boolean)))
        .sort((a, b) => b - a);

    trainerYSel.innerHTML = '<option value="all">All Years</option>' + years.map(year => `<option value="${year}">${year}</option>`).join('');
    trainerYSel.value = 'all';
    switchTrainerTab('providers');
}

function setParticipantsModalLoading() {
    const title = document.getElementById('partT');
    const subtitle = document.getElementById('partS');
    const body = document.getElementById('partBd');
    const pager = document.getElementById('pgBs');
    const info = document.getElementById('pgI');

    if (title) title.textContent = 'Participants';
    if (subtitle) subtitle.textContent = 'Loading participant list...';
    if (body) body.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1.4rem;color:var(--muted)">Loading participants...</td></tr>';
    if (pager) pager.innerHTML = '';
    if (info) info.textContent = '';
}

function renderParticipantsModal(payload) {
    currentParticipants = Array.isArray(payload.participants) ? payload.participants : [];
    expandedParticipants.clear();
    participantPage = 1;
    participantSearch = '';
    document.getElementById('partT').textContent = `Participants — ${payload.provider_name || currentProviderDetail?.TP_Name || '—'}` + (payload.course_name ? ` — ${payload.course_name}` : '');
    document.getElementById('partS').textContent = `${payload.participant_count || currentParticipants.length || 0} total registered participants`;
    document.getElementById('partSearch').value = '';
    renderParticipants();
}

function downloadParticipants() {
    const providerId = currentParticipantsProviderId || (currentProviderDetail ? currentProviderDetail.TP_ID : null);
    if (!providerId) {
        showToast('⚠️ Provider not selected');
        return;
    }

    const url = new URL('api/download-participants.php', window.location.href);
    url.searchParams.set('id', String(providerId));
    if (currentParticipantsItemId) url.searchParams.set('item_id', String(currentParticipantsItemId));

    // trigger download
    window.location = url.toString();
}

// Open provider modal
function openProviderModal(id) {
    const modal = document.getElementById('provOv');
    if (!modal) return;

    modal.classList.add('open');
    syncBodyLock();

    const cacheKey = getProviderCacheKey(id);
    if (providerDetailCache.has(cacheKey)) {
        renderProviderModal(providerDetailCache.get(cacheKey));
        loadParticipantsData(id).catch(() => {});
        return;
    }

    setProviderModalLoading();
    loadProviderDetail(id)
        .then(provider => {
            renderProviderModal(provider);
            loadParticipantsData(id).catch(() => {});
        })
        .catch(err => {
            document.getElementById('mN').textContent = 'Could not load provider details';
            document.getElementById('histC').innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">Please try again.</div>';
            showToast('⚠️ Could not load provider details');
            console.error(err);
        });
}

function openTrainerModal(id) {
    const modal = document.getElementById('trainerOv');
    if (!modal) return;

    modal.classList.add('open');
    syncBodyLock();

    const cacheKey = getTrainerCacheKey(id);
    if (trainerDetailCache.has(cacheKey)) {
        renderTrainerModal(trainerDetailCache.get(cacheKey));
        return;
    }

    setTrainerModalLoading();
    loadTrainerDetail(id)
        .then(trainer => {
            renderTrainerModal(trainer);
        })
        .catch(err => {
            document.getElementById('trainerName').textContent = 'Could not load trainer details';
            document.getElementById('trainerProvidersC').innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">Please try again.</div>';
            showToast('⚠️ Could not load trainer details');
            console.error(err);
        });
}

function closeTrainerModal() {
    document.getElementById('trainerOv').classList.remove('open');
    syncBodyLock();
}

let pendingTrainerFlagId = null;
let pendingTrainerFlagIsRed = false;
let pendingTrainerFlagReason = '';

const TRAINER_FLAG_REASON_LABELS = {
    'Unprofessional conduct': 'Unprofessional conduct - Behavioural issues, misconduct, or complaints from participants.',
    'Poor training quality': 'Poor training quality - Below-standard delivery, outdated content, or low feedback scores.',
    'Compliance or legal concern': 'Compliance or legal concern - Regulatory breach, credential issues, or ongoing legal matter.',
    'Reliability issues': 'Reliability issues - Repeated no-shows, late cancellations, or session disruptions.'
};

function openTrainerRedFlagModal(id, isRedFlagged) {
    pendingTrainerFlagId = Number(id);
    pendingTrainerFlagIsRed = !!isRedFlagged;
    pendingTrainerFlagReason = '';

    const modal = document.getElementById('trainerFlagOv');
    if (!modal) return;

    modal.classList.add('open');
    renderTrainerRedFlagModal();
    syncBodyLock();

    const cacheKey = getTrainerCacheKey(id);
    if (trainerDetailCache.has(cacheKey)) {
        currentTrainerDetail = trainerDetailCache.get(cacheKey);
        renderTrainerRedFlagModal();
        return;
    }

    loadTrainerDetail(id)
        .then(trainer => {
            if (pendingTrainerFlagId !== Number(id)) return;
            currentTrainerDetail = trainer;
            pendingTrainerFlagIsRed = !!trainer.Trainer_StatusActive;
            renderTrainerRedFlagModal();
        })
        .catch(err => {
            console.error(err);
            showToast('⚠️ Could not load trainer details');
        });
}

function renderTrainerRedFlagModal() {
    const name = document.getElementById('trainerFlagName');
    const title = document.getElementById('trainerFlagTitle');
    const message = document.getElementById('trainerFlagMessage');
    const reasonWrap = document.getElementById('trainerFlagReasonWrap');
    const submitBtn = document.getElementById('trainerFlagSubmitBtn');
    const reasonBtn = document.getElementById('trainerFlagReasonBtn');
    const reasonMenu = document.getElementById('trainerFlagReasonMenu');

    const isRemoveMode = pendingTrainerFlagIsRed;
    const trainerName = currentTrainerDetail?.Trainer_Name || 'Loading trainer...';

    if (name) name.textContent = trainerName;
    if (title) title.textContent = isRemoveMode ? 'Remove Red Flag' : 'Red Flag Trainer';
    if (message) {
        message.textContent = isRemoveMode
            ? 'This will remove the trainer red flag and revert the status to Green Flag.'
            : 'Choose a reason for the red flag before submitting.';
    }
    if (reasonWrap) {
        reasonWrap.style.display = isRemoveMode ? 'none' : 'block';
    }
    if (reasonBtn) {
        reasonBtn.textContent = pendingTrainerFlagReason
            ? (TRAINER_FLAG_REASON_LABELS[pendingTrainerFlagReason] || pendingTrainerFlagReason)
            : 'Select a reason';
    }
    if (reasonMenu) {
        reasonMenu.classList.remove('open');
    }
    if (submitBtn) {
        submitBtn.textContent = 'Submit';
    }
}

function toggleTrainerFlagReasonMenu() {
    if (pendingTrainerFlagIsRed) return;
    const menu = document.getElementById('trainerFlagReasonMenu');
    if (!menu) return;
    menu.classList.toggle('open');
}

function chooseTrainerFlagReason(reason, label) {
    pendingTrainerFlagReason = reason || '';
    const btn = document.getElementById('trainerFlagReasonBtn');
    if (btn) btn.textContent = label || 'Select a reason';
    const menu = document.getElementById('trainerFlagReasonMenu');
    if (menu) menu.classList.remove('open');
}

function closeTrainerFlagModal() {
    const modal = document.getElementById('trainerFlagOv');
    if (modal) modal.classList.remove('open');
    pendingTrainerFlagId = null;
    pendingTrainerFlagIsRed = false;
    pendingTrainerFlagReason = '';
    const reasonBtn = document.getElementById('trainerFlagReasonBtn');
    if (reasonBtn) reasonBtn.textContent = 'Select a reason';
    const reasonMenu = document.getElementById('trainerFlagReasonMenu');
    if (reasonMenu) reasonMenu.classList.remove('open');
    syncBodyLock();
}

function submitTrainerRedFlag() {
    if (!pendingTrainerFlagId) return;

    const reason = pendingTrainerFlagIsRed ? null : (pendingTrainerFlagReason || '').trim();

    if (!pendingTrainerFlagIsRed && !reason) {
        showToast('⚠️ Please choose a reason for the red flag.');
        return;
    }

    const trainerId = pendingTrainerFlagId;
    const isRemoveMode = pendingTrainerFlagIsRed;
    closeTrainerFlagModal();

    fetch('api/update-trainer-red-flag.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: trainerId,
            is_red_flag: !isRemoveMode,
            reason
        })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            throw new Error(data.error || 'Unable to update trainer red flag');
        }

        const cacheKey = getTrainerCacheKey(trainerId);
        trainerDetailCache.delete(cacheKey);
        listDataCache.clear();
        listDataInflight.clear();

        if (document.getElementById('trainerOv')?.classList.contains('open')) {
            loadTrainerDetail(trainerId)
                .then(trainer => {
                    currentTrainerDetail = trainer;
                    renderTrainerModal(trainer);
                })
                .catch(() => {});
        }

        loadData();
        updateStats();
        showToast(isRemoveMode ? '✓ Red flag removed' : '✓ Trainer red flagged');
    })
    .catch(err => {
        console.error(err);
        showToast('⚠️ Could not update trainer red flag');
    });
}

function switchTrainerTab(tabName, button) {
    currentTrainerTab = tabName;
    const panels = document.querySelectorAll('#trainerTab-providers, #trainerTab-courses, #trainerTab-status, #trainerTab-remarks');
    panels.forEach(p => p.classList.remove('active'));
    
    const buttons = document.querySelectorAll('#ttab-providers, #ttab-courses, #ttab-status, #ttab-remarks');
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

function renderTrainerStatusHistory() {
    const histC = document.getElementById('trainerStatusHistC');
    if (!histC || !currentTrainerDetail) return;

    const history = Array.isArray(currentTrainerDetail.status_history) ? currentTrainerDetail.status_history : [];
    if (!history.length) {
        histC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No status history yet</div>';
        return;
    }

    histC.innerHTML = history.map(row => {
        const rawStatus = row.Trainer_StatusDisplay || row.Trainer_Status || '';
        const displayStatus = rawStatus || 'Green Flag';
        const badgeClass = displayStatus === 'Red Flag' ? 'b-b' : 'b-a';
        const startDate = formatDisplayDate(row.Trainer_StatusStartDate);
        const endDate = row.Trainer_StatusEndDate ? ` → ${formatDisplayDate(row.Trainer_StatusEndDate)}` : '';
        const reason = row.Trainer_StatusReasoning ? `<div class="status-note">Reason: ${escapeHtml(String(row.Trainer_StatusReasoning))}</div>` : '';
        return `
            <div class="status-card">
                <div class="status-top">
                    <span class="bdg ${badgeClass}">${escapeHtml(displayStatus)}</span>
                    <span class="status-date">${startDate}${endDate}</span>
                </div>
                ${reason}
            </div>
        `;
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

function buildProviderSummaryBuckets() {
    const courses = Array.isArray(currentProviderDetail?.courses) ? currentProviderDetail.courses : [];
    const buckets = new Map();

    courses.forEach(course => {
        const category = String(course.Item_Category || '').trim();
        if (!category) return;
        const pax = Number(course.participant_count || 0);
        const existing = buckets.get(category) || { category, pax: 0, courses: 0 };
        existing.pax += pax;
        existing.courses += 1;
        buckets.set(category, existing);
    });

    return Array.from(buckets.values()).sort((a, b) => b.pax - a.pax || b.courses - a.courses || a.category.localeCompare(b.category));
}

function splitTreemapItems(items) {
    if (!items.length) return [[], []];
    const total = items.reduce((sum, item) => sum + item.value, 0);
    if (items.length === 1) return [items, []];

    let left = [];
    let right = items.slice();
    let leftTotal = 0;
    for (let i = 0; i < items.length; i++) {
        const nextTotal = leftTotal + items[i].value;
        if (left.length > 0 && nextTotal >= total / 2) break;
        left.push(items[i]);
        leftTotal = nextTotal;
        right = items.slice(i + 1);
    }

    if (!left.length) {
        left = [items[0]];
        right = items.slice(1);
    }

    return [left, right];
}

function layoutTreemap(items, x, y, width, height, horizontal = true) {
    if (!items.length) return [];
    if (items.length === 1) {
        return [{ ...items[0], x, y, width, height }];
    }

    const total = items.reduce((sum, item) => sum + item.value, 0) || 1;
    const [firstGroup, secondGroup] = splitTreemapItems(items);
    const firstTotal = firstGroup.reduce((sum, item) => sum + item.value, 0);
    const firstRatio = firstTotal / total;

    if (horizontal) {
        const splitHeight = height * firstRatio;
        return [
            ...layoutTreemap(firstGroup, x, y, width, splitHeight, !horizontal),
            ...layoutTreemap(secondGroup, x, y + splitHeight, width, height - splitHeight, !horizontal)
        ];
    }

    const splitWidth = width * firstRatio;
    return [
        ...layoutTreemap(firstGroup, x, y, splitWidth, height, !horizontal),
        ...layoutTreemap(secondGroup, x + splitWidth, y, width - splitWidth, height, !horizontal)
    ];
}

function renderProviderSummaryTab() {
    const summaryC = document.getElementById('providerSummaryC');
    if (!summaryC || !currentProviderDetail) return;

    const buckets = buildProviderSummaryBuckets();
    if (!buckets.length) {
        summaryC.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--muted)">No category data available yet</div>';
        return;
    }

    const totalPax = buckets.reduce((sum, bucket) => sum + bucket.pax, 0) || 1;
    const colors = ['#0080c6', '#0d9488', '#7c3aed', '#16a34a', '#f59e0b', '#dc2626', '#2563eb', '#0891b2'];
    const nodes = layoutTreemap(
        buckets.map((bucket, index) => ({
            ...bucket,
            value: bucket.pax,
            color: colors[index % colors.length]
        })),
        0,
        0,
        100,
        100,
        true
    );

    summaryC.innerHTML = `
        <div class="provider-summary-head">
            <div>
                <div class="provider-summary-title">Area of Expertise by Course Category</div>
                <div class="provider-summary-subtitle">Heatmap populated based on total number of participants enrollment</div>
            </div>
            <div class="provider-summary-total">${buckets.length} categories · ${totalPax} pax</div>
        </div>
        <div class="provider-treemap" aria-label="Area of expertise treemap">
            ${nodes.map(node => {
                const percent = ((node.value / totalPax) * 100).toFixed(1);
                const minVisible = node.width > 8 && node.height > 8;
                const fontSize = Math.max(11, Math.min(18, Math.min(node.width, node.height) * 0.18));
                return `
                    <div class="provider-treemap-tile" style="left:${node.x}%;top:${node.y}%;width:${node.width}%;height:${node.height}%;background:${node.color};" title="${escapeHtml(node.category)} · ${percent}% · ${node.pax} pax">
                        <div class="provider-treemap-overlay"></div>
                        <div class="provider-treemap-label" style="font-size:${fontSize}px;">
                            <div class="provider-treemap-name">${escapeHtml(node.category)}</div>
                            ${minVisible ? `<div class="provider-treemap-meta">${percent}% · ${node.pax} pax</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function openStatusModal() {
    if (!currentProviderDetail) return;
    pendingStatus = currentProviderDetail.TP_Status || 'Active';
    pendingBlacklistReason = currentProviderDetail.blacklistReason || currentProviderDetail.TP_StatusReasoning || '';
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
    pendingBlacklistReason = '';
    const reasonBtn = document.getElementById('blReasonBtn');
    if (reasonBtn) reasonBtn.textContent = 'Select a reason';
    const reasonMenu = document.getElementById('blReasonMenu');
    if (reasonMenu) reasonMenu.classList.remove('open');
    syncBodyLock();
}

function toggleBlacklistReasonMenu() {
    if (pendingStatus !== 'Blacklisted') return;
    const menu = document.getElementById('blReasonMenu');
    if (!menu) return;
    menu.classList.toggle('open');
}

function chooseBlacklistReason(reason) {
    pendingBlacklistReason = reason || '';
    const btn = document.getElementById('blReasonBtn');
    if (btn) btn.textContent = reason || 'Select a reason';
    const menu = document.getElementById('blReasonMenu');
    if (menu) menu.classList.remove('open');
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
    const reasonBtn = document.getElementById('blReasonBtn');
    const reasonMenu = document.getElementById('blReasonMenu');
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
    if (reasonBtn) {
        reasonBtn.textContent = pendingBlacklistReason || 'Select a reason';
    }
    if (reasonMenu) {
        reasonMenu.classList.remove('open');
    }
}

function confirmStatus() {
    if (!currentProviderDetail) return;
    const reason = pendingStatus === 'Blacklisted' ? (pendingBlacklistReason || '').trim() : '';
    const blacklistUntil = document.getElementById('blUntilTa')?.value || '';
    if (pendingStatus === 'Blacklisted' && !reason) {
        showToast('⚠️ Please provide a reason for blacklisting.');
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
    // store for downloads
    currentParticipantsProviderId = actualProviderId;
    currentParticipantsItemId = itemId || null;
    
    if (!actualProviderId) {
        showToast('⚠️ Provider not selected');
        return;
    }

    const modal = document.getElementById('partOv');
    if (!modal) return;

    modal.classList.add('open');
    syncBodyLock();

    const cacheKey = getParticipantsCacheKey(actualProviderId, itemId);
    if (participantListCache.has(cacheKey)) {
        renderParticipantsModal(participantListCache.get(cacheKey));
        return;
    }

    setParticipantsModalLoading();
    loadParticipantsData(actualProviderId, itemId)
        .then(payload => {
            renderParticipantsModal(payload);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('partS').textContent = 'Unable to load participants';
            document.getElementById('partBd').innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1.4rem;color:var(--muted)">Could not load participant list</td></tr>';
            showToast('⚠️ Could not load participant list');
        });
}

function closeParticipantsModal() {
    document.getElementById('partOv').classList.remove('open');
    syncBodyLock();
}

function switchProviderTab(tab, btn) {
    currentProviderTab = tab;
    document.querySelectorAll('#provOv .ptab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('#provOv .ptab-panel').forEach(el => el.classList.remove('active'));

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

function getParticipantGroupKey(row) {
    const token = String(row.Participant_Token || '').trim();
    if (token) return `tok:${token}`;

    const id = String(row.Participant_ID || '').trim();
    if (id) return `id:${id}`;

    const name = String(row.Participant_Name || '').trim().toLowerCase();
    const dept = String(row.Participant_Department || '').trim().toLowerCase();
    return `name:${name}|dept:${dept}`;
}

function buildParticipantGroups(rows) {
    const groups = new Map();

    rows.forEach(row => {
        const key = getParticipantGroupKey(row);
        if (!groups.has(key)) {
            groups.set(key, {
                key,
                Participant_Name: row.Participant_Name || '—',
                Participant_Department: row.Participant_Department || '—',
                courses: []
            });
        }

        const group = groups.get(key);
        group.courses.push({
            Course_Name: row.Course_Name || '—',
            Completion_Year: row.Completion_Year || '—'
        });
    });

    const grouped = Array.from(groups.values());
    grouped.forEach(group => {
        group.courses.sort((a, b) => {
            const yearA = Number(a.Completion_Year || 0);
            const yearB = Number(b.Completion_Year || 0);
            if (yearA !== yearB) return yearB - yearA;
            return String(a.Course_Name || '').localeCompare(String(b.Course_Name || ''));
        });
        group.course_count = group.courses.length;
    });

    return grouped.sort((a, b) => String(a.Participant_Name || '').localeCompare(String(b.Participant_Name || '')));
}

function toggleParticipantExpand(key) {
    if (!key) return;
    if (expandedParticipants.has(key)) {
        expandedParticipants.delete(key);
    } else {
        expandedParticipants.add(key);
    }
    renderParticipants();
}

function renderParticipants() {
    const body = document.getElementById('partBd');
    const pager = document.getElementById('pgBs');
    const info = document.getElementById('pgI');
    if (!body || !pager || !info) return;

    const grouped = buildParticipantGroups(currentParticipants);
    const filtered = grouped.filter(group => {
        if (!participantSearch) return true;
        const matchesIdentity = String(group.Participant_Name || '').toLowerCase().includes(participantSearch)
            || String(group.Participant_Department || '').toLowerCase().includes(participantSearch);
        if (matchesIdentity) return true;

        return group.courses.some(course => {
            return String(course.Course_Name || '').toLowerCase().includes(participantSearch)
                || String(course.Completion_Year || '').toLowerCase().includes(participantSearch);
        });
    });

    const pageSize = 10;
    const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
    participantPage = Math.min(participantPage, pageCount);
    const start = (participantPage - 1) * pageSize;
    const pageRows = filtered.slice(start, start + pageSize);

    body.innerHTML = pageRows.length ? pageRows.map((group, idx) => {
        const isExpanded = expandedParticipants.has(group.key);
        const details = group.courses.map(course => `
            <div class="part-course-row">
                <span class="part-course-name">${escapeHtml(String(course.Course_Name || '—'))}</span>
                <span class="part-course-year">${escapeHtml(String(course.Completion_Year || '—'))}</span>
            </div>
        `).join('');

        return `
        <tr class="part-row-summary ${isExpanded ? 'expanded' : ''}" onclick="toggleParticipantExpand(decodeURIComponent('${encodeURIComponent(group.key)}'))">
            <td>${start + idx + 1}</td>
            <td><strong>${escapeHtml(String(group.Participant_Name || '—'))}</strong><span class="part-expand">${isExpanded ? '▾' : '▸'}</span></td>
            <td><span class="dtag">${escapeHtml(String(group.Participant_Department || '—'))}</span></td>
            <td><span class="part-course-count">${group.course_count} ${group.course_count === 1 ? 'course' : 'courses'}</span></td>
        </tr>
        ${isExpanded ? `<tr class="part-row-detail"><td colspan="4"><div class="part-detail-wrap"><div class="part-detail-head"><span>Course</span><span>Completion Year</span></div>${details}</div></td></tr>` : ''}
        `;
    }).join('') : `<tr><td colspan="4" style="text-align:center;padding:1.4rem;color:var(--muted)">No participants found</td></tr>`;

    info.textContent = filtered.length ? `Showing ${start + 1}-${Math.min(start + pageSize, filtered.length)} of ${filtered.length}` : 'Showing 0 of 0';
    pager.innerHTML = '';
    const buttons = [];
    buttons.push({ label: '‹', page: Math.max(1, participantPage - 1), disabled: participantPage === 1 });

    const visiblePages = [];
    const addVisiblePage = (page) => {
        if (page >= 1 && page <= pageCount && !visiblePages.includes(page)) {
            visiblePages.push(page);
        }
    };

    if (pageCount <= 7) {
        for (let i = 1; i <= pageCount; i++) addVisiblePage(i);
    } else {
        addVisiblePage(1);
        addVisiblePage(2);
        addVisiblePage(participantPage - 1);
        addVisiblePage(participantPage);
        addVisiblePage(participantPage + 1);
        addVisiblePage(pageCount - 1);
        addVisiblePage(pageCount);
        visiblePages.sort((a, b) => a - b);
    }

    let lastPage = null;
    visiblePages.forEach(page => {
        if (lastPage !== null && page - lastPage > 1) {
            buttons.push({ label: '…', page: null, disabled: true });
        }
        buttons.push({ label: String(page), page, active: page === participantPage });
        lastPage = page;
    });

    buttons.push({ label: '›', page: Math.min(pageCount, participantPage + 1), disabled: participantPage === pageCount });
    buttons.forEach(btn => {
        const el = document.createElement('button');
        el.className = 'pb' + (btn.active ? ' active' : '');
        el.textContent = btn.label;
        if (btn.disabled || btn.page === null) el.disabled = true;
        if (!btn.disabled && btn.page !== null) el.onclick = () => { participantPage = btn.page; renderParticipants(); };
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
    if (file.size > MAX_UPLOAD_BYTES) {
        selectedFile = null;
        document.getElementById('finfo').style.display = 'none';
        document.getElementById('fi2').value = '';
        showToast('⚠️ File too large (' + formatBytes(file.size) + '). Max allowed is 32 MB.');
        return;
    }

    selectedFile = file;
    document.getElementById('finfo').style.display = 'flex';
    document.getElementById('fn').textContent = name;
}

function openUpload() {
    document.getElementById('upOv').classList.add('open');
    document.body.style.overflow = 'hidden';
    resetUpload();
    switchUploadTab('upload');   // always open on Upload tab
    loadUploadHistory();         // pre-load so the badge count is fresh
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

// ── Upload modal tab switching ──
function switchUploadTab(tab) {
    document.querySelectorAll('.u-tab-btn').forEach(function(btn) { btn.classList.remove('active'); });
    var activeBtn = document.getElementById('utab-' + tab);
    if (activeBtn) activeBtn.classList.add('active');

    var uploadPanel  = document.getElementById('upContent-upload');
    var historyPanel = document.getElementById('upContent-history');
    if (uploadPanel)  uploadPanel.style.display  = (tab === 'upload')  ? '' : 'none';
    if (historyPanel) historyPanel.style.display  = (tab === 'history') ? '' : 'none';

    if (tab === 'history') loadUploadHistory();
}

// ── Load and render upload history ──
function loadUploadHistory() {
    var list  = document.getElementById('uploadHistoryList');
    var badge = document.getElementById('upHistBadge');
    if (!list) return;

    list.innerHTML = '<div style="text-align:center;padding:1.5rem;color:var(--muted);font-family:\'Calibri\',sans-serif;font-size:0.85rem;">Loading…</div>';

    fetch('api/get-uploads.php', { credentials: 'same-origin', cache: 'no-store' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                list.innerHTML = '<div style="text-align:center;padding:1.5rem;color:var(--red);font-family:\'Calibri\',sans-serif;font-size:0.85rem;">Failed to load history.</div>';
                return;
            }
            var uploads = data.uploads || [];
            if (badge) badge.textContent = uploads.length;

            if (!uploads.length) {
                list.innerHTML = '<div style="text-align:center;padding:2rem 1rem;color:var(--muted);font-family:\'Calibri\',sans-serif;font-size:0.85rem;">No uploads yet. Use the Upload tab to import data.</div>';
                return;
            }

            list.innerHTML = uploads.map(function(u) {
                var date = '—';
                if (u.Upload_Date) {
                    try { date = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(u.Upload_Date)); } catch(e) {}
                }
                var isRemoved = u.UI_Status === 'Removed';
                var badgeStyle = isRemoved
                    ? 'background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;'
                    : 'background:#dcfce7;color:#16a34a;border:1px solid #86efac;';
                var rowOpacity = isRemoved ? 'opacity:0.55;' : '';
                var nameStyle = isRemoved
                    ? 'font-family:\'Calibri\',sans-serif;font-weight:700;font-size:0.87rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;text-decoration:line-through;'
                    : 'font-family:\'Calibri\',sans-serif;font-weight:700;font-size:0.87rem;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;';
                var safeName = escHtml(u.Filename);
                var filenameAttr = safeName.replace(/"/g, '&quot;');
                return '<div class="upload-hist-item" style="' + rowOpacity + '">' +
                    '<div style="display:flex;align-items:center;gap:0.65rem;min-width:0;">' +
                        '<span style="font-size:1.35rem;flex-shrink:0;">&#128202;</span>' +
                        '<div style="min-width:0;">' +
                            '<div style="' + nameStyle + '" title="' + filenameAttr + '">' + safeName + '</div>' +
                            '<div style="font-size:0.73rem;color:var(--muted);font-family:\'Calibri\',sans-serif;">' + (isRemoved ? '0' : u.Record_Count.toLocaleString()) + ' records · ' + date + '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0;">' +
                        '<span style="' + badgeStyle + 'border-radius:10px;padding:2px 8px;font-size:0.72rem;font-weight:700;font-family:\'Calibri\',sans-serif;">' + escHtml(u.UI_Status) + '</span>' +
                        (!isRemoved ? '<button class="upload-hist-remove-btn" data-uid="' + u.Upload_ID + '" data-fname="' + filenameAttr + '" onclick="removeUpload(+this.dataset.uid, this.dataset.fname)">&#128465; Remove</button>' : '') +
                    '</div>' +
                '</div>';
            }).join('');
        })
        .catch(function() {
            list.innerHTML = '<div style="text-align:center;padding:1.5rem;color:var(--red);font-family:\'Calibri\',sans-serif;font-size:0.85rem;">Error loading history.</div>';
        });
}

// ── Remove an upload and its enrollment data ──
// ── Remove an upload and its enrollment data ──
var _pendingRemoveId   = null;
var _pendingRemoveName = null;

function removeUpload(uploadId, filename) {
    _pendingRemoveId   = uploadId;
    _pendingRemoveName = filename;
    var fnEl = document.getElementById('removeConfirmFilename');
    if (fnEl) fnEl.textContent = '\u201C' + filename + '\u201D';
    var btn = document.getElementById('removeConfirmBtn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg> Yes, remove';
    }
    var ov = document.getElementById('removeConfirmOv');
    if (ov) { ov.classList.add('open'); document.body.style.overflow = 'hidden'; }
}

function closeRemoveConfirm() {
    var ov = document.getElementById('removeConfirmOv');
    if (ov) ov.classList.remove('open');
    document.body.style.overflow = '';
    _pendingRemoveId   = null;
    _pendingRemoveName = null;
}

function confirmRemoveUpload() {
    if (!_pendingRemoveId) return;
    var uploadId = _pendingRemoveId;
    var btn = document.getElementById('removeConfirmBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Removing\u2026'; }

    fetch('api/remove-upload.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ upload_id: uploadId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        closeRemoveConfirm();
        if (data.success) {
            showToast('\u2705 Upload data removed successfully');
            setTimeout(function() { window.location.reload(); }, 1000);
        } else {
            showToast('\u274C ' + (data.error || 'Failed to remove upload'));
        }
    })
    .catch(function() {
        closeRemoveConfirm();
        showToast('\u274C Error communicating with server');
    });
}

// ── HTML-escape helper ──
function escHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function parseUploadResponse(response) {
    return response.text().then(text => {
        let payload = null;
        try {
            payload = text ? JSON.parse(text) : {};
        } catch (err) {
            // Strip HTML warning markup when backend returns PHP warnings/errors.
            const cleaned = (text || '')
                .replace(/<[^>]*>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
            throw new Error(cleaned || 'Server returned an invalid response');
        }

        if (!response.ok) {
            throw new Error((payload && payload.message) ? payload.message : ('Upload failed with status ' + response.status));
        }
        return payload;
    });
}

function uploadWithProgress(url, formData, onProgress) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.upload.onprogress = onProgress;
        xhr.onload = function() {
            resolve({
                ok: xhr.status >= 200 && xhr.status < 300,
                status: xhr.status,
                text: () => Promise.resolve(xhr.responseText)
            });
        };
        xhr.onerror = function() {
            reject(new Error('Network error during upload'));
        };
        xhr.send(formData);
    });
}

function performUpload() {
    if (!selectedFile) {
        showToast('⚠️ Select a file first');
        return;
    }

    confirmImport();
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

    const uploadStartTime = performance.now();
    uploadWithProgress('api/upload.php', formData, function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            fill.style.width = percentComplete + '%';
            if (percentComplete < 100) {
                lbl.textContent = 'Uploading (' + Math.round(percentComplete) + '%)…';
            } else {
                lbl.textContent = 'Processing data…';
            }
        }
    })
    .then(parseUploadResponse)
    .then(result => {
        if (result.success) {
            const timeTaken = ((performance.now() - uploadStartTime) / 1000).toFixed(1);
            fill.style.width = '100%';
            lbl.textContent = 'Upload complete ✓';
            setTimeout(() => {
                // close preview without reopening upload
                closeUploadPreview(false);
                showToast('✅ Database updated successfully!');
                showSuccess(result.stats || {}, timeTaken, selectedFile);
                loadData();
                updateStats();
            }, 700);
        } else {
            showToast('❌ ' + (result && result.message ? result.message : 'Upload failed'));
            prog.style.display = 'none';
        }
    })
    .catch(err => {
        showToast('❌ Upload error: ' + (err && err.message ? err.message : String(err || 'Unknown error')));
        prog.style.display = 'none';
    });
}

function formatBytes(bytes, decimals = 1) {
    if (!+bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

function showSuccess(stats, timeTaken, file) {
    document.getElementById('successFileName').textContent = file.name;
    document.getElementById('successFileSize').textContent = '· ' + formatBytes(file.size);
    document.getElementById('successCounts').innerHTML = `<strong style="font-weight: 700;">${stats.providers_added || 0} providers</strong> and <strong style="font-weight: 700;">${stats.trainers_added || 0} trainers</strong> updated`;
    document.getElementById('successTime').textContent = timeTaken + 's';
    
    const now = new Date();
    const formatter = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
    document.getElementById('successDate').textContent = '· ' + formatter.format(now);
    
    document.getElementById('successOv').classList.add('open');
}

function closeSuccess() {
    window.location.reload();
}

function downloadExport() {
    const query = getCurrentListQuery();
    const params = new URLSearchParams({
        search: query.search || '',
        status: currentView === 'prov' ? (query.status || 'all') : 'all',
        sort: query.sort || 'asc'
    });

    const url = (currentView === 'train') ? 'api/download-trainers.php?' + params.toString() : 'api/download-providers.php?' + params.toString();
    const link = document.createElement('a');
    link.href = url;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function downloadProviderCourses() {
    if (!currentProviderDetail || !currentProviderDetail.TP_ID) {
        showToast('⚠️ Provider not selected');
        return;
    }

    const url = new URL('api/download-provider-courses.php', window.location.href);
    url.searchParams.set('id', String(currentProviderDetail.TP_ID));

    const link = document.createElement('a');
    link.href = url.toString();
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function downloadTrainerCourses() {
    if (!currentTrainerDetail || !currentTrainerDetail.Trainer_ID) {
        showToast('⚠️ Trainer not selected');
        return;
    }

    const url = new URL('api/download-trainer-courses.php', window.location.href);
    url.searchParams.set('id', String(currentTrainerDetail.Trainer_ID));

    const link = document.createElement('a');
    link.href = url.toString();
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function downloadComplaints() {
    const term = document.getElementById('compSearchInput').value;
    const url = new URL('api/download-complaints.php', window.location.href);
    if (term) url.searchParams.set('search', term);

    const link = document.createElement('a');
    link.href = url.toString();
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

// Toast notifications
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = (msg === undefined || msg === null || msg === '') ? 'Unknown error' : String(msg);
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
            const trainerName = course.Trainer_Name ? String(course.Trainer_Name) : '';
            const trainerId = course.Trainer_ID || '';
            const trainerSpan = trainerName ? `<div style="flex-shrink:0;font-size:.75rem;color:var(--muted);white-space:nowrap;"><a href="#" onpointerenter="prefetchTrainerModal(${trainerId})" onfocus="prefetchTrainerModal(${trainerId})" onclick="event.preventDefault(); closeModal(); openTrainerModal(${trainerId})" style="color:inherit;text-decoration:none;">${escapeHtml(trainerName)}</a></div>` : '';
            return `
            <div class="hr2">
                <div class="ht">${course.Item_Name || 'N/A'}</div>
                <div class="hch">
                    ${trainerSpan}
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

    updateStatusFilterMenu();
    loadData();
    updateStats();
});
</script>

<?php if ($_SESSION['role'] === 'admin'): ?>
<button class="fab-complaint" onclick="openComplaintModal()" title="Complaints">
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
</button>
<?php endif; ?>

<!-- Complaint Modal -->
<div class="ov" id="complaintOv">
    <div class="pm" style="max-width:800px">
        <div class="mh">
            <div>
                <div class="mpn">Complaints & Feedback</div>
                <div style="font-size:0.8rem;color:rgba(255,255,255,0.7);font-family:'Calibri',sans-serif">Manage trainer & provider complaints</div>
            </div>
            <button class="mc" onclick="closeComplaintModal()">×</button>
        </div>
        <div class="ptabs">
            <button class="ptab active" id="ctab-new" onclick="switchComplaintTab('new')">New Complaint</button>
            <button class="ptab" id="ctab-update" onclick="switchComplaintTab('update')">Update Status</button>
        </div>
        
        <!-- New Complaint Tab -->
        <div class="mb2" id="compContent-new">
            <form id="newComplaintForm" onsubmit="submitNewComplaint(event)">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Date of Complaint *</label>
                        <input type="date" id="compDate" class="si" required style="width:100%">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Priority *</label>
                        <select id="compPriority" class="si" required style="width:100%">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Employee Name *</label>
                        <input type="text" id="compEmpName" class="si" required style="width:100%">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Employee ID *</label>
                        <input type="text" id="compEmpId" class="si" required style="width:100%">
                    </div>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Department *</label>
                        <select id="compDept" class="si" required style="width:100%">
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">LearnOps Assigned</label>
                        <input type="text" id="compLearnOps" class="si" style="width:100%">
                    </div>
                </div>
                
                <div style="margin-bottom:1rem;position:relative;">
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Training Provider *</label>
                    <input type="text" id="compTpSearch" class="si" placeholder="Search provider name..." required style="width:100%" oninput="filterCompTp('compTpSearch', 'compTpDropdown', 'compTpId')">
                    <input type="hidden" id="compTpId">
                    <div id="compTpDropdown" class="trainer-flag-reason-menu" style="top:calc(100% - 4px);width:100%"></div>
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Complaint Category *</label>
                    <input type="text" id="compCategory" class="si" placeholder="E.g., Poor content, unprofessional..." required style="width:100%">
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Complaint Summary</label>
                    <textarea id="compSummary" class="si" rows="3" style="width:100%;resize:vertical;"></textarea>
                </div>
                
                <div style="display:flex;justify-content:flex-end;gap:0.5rem;">
                    <button type="button" class="stm-cancel" onclick="document.getElementById('newComplaintForm').reset();document.getElementById('compTpId').value=''">Clear</button>
                    <button type="submit" class="stm-confirm">Submit Complaint</button>
                </div>
            </form>
        </div>
        
        <!-- Update Status Tab -->
        <div class="mb2" id="compContent-update" style="display:none;">
            <!-- List Section -->
            <div id="compListSection">
                <div style="display:flex;gap:0.5rem;margin-bottom:1rem;flex-wrap:wrap;">
                    <input type="text" id="compSearchInput" class="si" placeholder="Search Case ID, Provider, Employee..." style="flex:0 1 200px">
                    <select id="compFilterYear" class="si" style="flex:0 1 100px">
                        <option value="">All Years</option>
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= 2020; $y--) {
                            echo "<option value='$y'>$y</option>";
                        }
                        ?>
                    </select>
                    <select id="compFilterStatus" class="si" style="flex:0 1 120px">
                        <option value="">All Status</option>
                        <option value="Open">Open</option>
                        <option value="Investigating">Investigating</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                    <button type="button" class="vb" onclick="fetchComplaints()">Search</button>
                </div>
                <div id="compListContainer" style="max-height:400px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:0.5rem;background:var(--cream);">
                    <!-- Dynamic list -->
                </div>
            </div>
            
            <!-- Edit Section -->
            <div id="compEditSection" style="display:none;">
                <button type="button" onclick="showComplaintList()" style="background:transparent;border:none;color:var(--blue);cursor:pointer;font-weight:700;font-size:0.85rem;margin-bottom:1rem;display:flex;align-items:center;gap:0.3rem;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg> Back to List
                </button>
                
                <form id="editComplaintForm" onsubmit="submitEditComplaint(event)">
                    <div style="background:var(--cream);padding:0.8rem;border-radius:8px;border-left:3px solid var(--blue);margin-bottom:1rem;font-family:'Calibri',sans-serif;display:flex;justify-content:space-between;align-items:center;">
                        <div><strong style="color:var(--ink);">Case ID:</strong> <span id="editCompCaseId" style="color:var(--blue);font-weight:700;"></span></div>
                        <input type="hidden" id="editCompCaseIdHidden">
                    </div>

                    <!-- Repeated Form Fields for Edit -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Date of Complaint *</label>
                            <input type="date" id="editCompDate" class="si" required style="width:100%">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Priority *</label>
                            <select id="editCompPriority" class="si" required style="width:100%">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Employee Name *</label>
                            <input type="text" id="editCompEmpName" class="si" required style="width:100%">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Employee ID *</label>
                            <input type="text" id="editCompEmpId" class="si" required style="width:100%">
                        </div>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Department *</label>
                            <select id="editCompDept" class="si" required style="width:100%"></select>
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">LearnOps Assigned</label>
                            <input type="text" id="editCompLearnOps" class="si" style="width:100%">
                        </div>
                    </div>
                    
                    <div style="margin-bottom:1rem;position:relative;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Training Provider *</label>
                        <input type="text" id="editCompTpSearch" class="si" required style="width:100%" oninput="filterCompTp('editCompTpSearch', 'editCompTpDropdown', 'editCompTpId')">
                        <input type="hidden" id="editCompTpId">
                        <div id="editCompTpDropdown" class="trainer-flag-reason-menu" style="top:calc(100% - 4px);width:100%"></div>
                    </div>
                    
                    <div style="margin-bottom:1rem;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Complaint Category *</label>
                        <input type="text" id="editCompCategory" class="si" required style="width:100%">
                    </div>
                    
                    <div style="margin-bottom:1rem;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Complaint Summary</label>
                        <textarea id="editCompSummary" class="si" rows="2" style="width:100%;resize:vertical;"></textarea>
                    </div>

                    <!-- Update Fields -->
                    <hr style="border:0;border-top:1px solid var(--border);margin:1.5rem 0;">
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Status</label>
                            <select id="editCompStatus" class="si" style="width:100%">
                                <option value="Open">Open</option>
                                <option value="Investigating">Investigating</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">LDCM Decision</label>
                            <select id="editCompDecision" class="si" style="width:100%">
                                <option value="No Action">No Action</option>
                                <option value="Pending Review">Pending Review</option>
                                <option value="Blacklist">Blacklist</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Decision Date</label>
                            <input type="date" id="editCompDecisionDate" class="si" style="width:100%">
                        </div>
                    </div>

                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:0.4rem;">Remarks</label>
                        <textarea id="editCompRemarks" class="si" rows="3" style="width:100%;resize:vertical;"></textarea>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:0.5rem;">
                        <button type="submit" class="stm-confirm">Save Updates</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Complaints Logic
let complaintsCache = [];
let departmentsCache = [];
function openComplaintModal() {
    const ov = document.getElementById('complaintOv');
    if (ov) {
        ov.classList.add('open');
        syncBodyLock();
        fetchDepartments();
        switchComplaintTab('new');
    }
}
function closeComplaintModal() {
    const ov = document.getElementById('complaintOv');
    if (ov) {
        ov.classList.remove('open');
        syncBodyLock();
        closeAllDeptMenus();
    }
}
function switchComplaintTab(tab) {
    document.getElementById('ctab-new').classList.toggle('active', tab === 'new');
    document.getElementById('ctab-update').classList.toggle('active', tab === 'update');
    document.getElementById('compContent-new').style.display = tab === 'new' ? 'block' : 'none';
    document.getElementById('compContent-update').style.display = tab === 'update' ? 'block' : 'none';
    
    if (tab === 'update') {
        showComplaintList();
        fetchComplaints();
    } else {
        document.getElementById('newComplaintForm').reset();
        document.getElementById('compTpId').value = '';
        setDeptValue('compDept', 'compDeptLabel', '');
    }
}
function fetchDepartments() {
    fetch('api/get-departments.php').then(r => r.json()).then(res => {
        if(res.success) {
            departmentsCache = res.data;
            renderDeptMenu('compDeptMenu', 'compDept', 'compDeptLabel');
            renderDeptMenu('editCompDeptMenu', 'editCompDept', 'editCompDeptLabel');
            const editHidden = document.getElementById('editCompDept');
            if (editHidden.dataset.pendingValue) {
                setDeptValue('editCompDept', 'editCompDeptLabel', editHidden.dataset.pendingValue);
                delete editHidden.dataset.pendingValue;
            }
        }
    }).catch(console.error);
}
function renderDeptMenu(menuId, hiddenId, labelId) {
    const btnId = menuId.replace('Menu', 'Btn');
    document.getElementById(menuId).innerHTML = departmentsCache.map(d => {
        const safe = escapeHtml(d).replace(/'/g, "\\'");
        return `<button type="button" class="trainer-flag-reason-item" onclick="selectDept('${hiddenId}','${labelId}','${menuId}','${btnId}','${safe}')">${escapeHtml(d)}</button>`;
    }).join('');
}
function toggleDeptMenu(menuId, btnId) {
    const menu = document.getElementById(menuId);
    const wasOpen = menu.classList.contains('open');
    closeAllDeptMenus();
    if (!wasOpen) {
        menu.classList.add('open');
        document.getElementById(btnId).classList.add('open');
    }
}
function closeAllDeptMenus() {
    ['compDeptMenu', 'editCompDeptMenu'].forEach(menuId => {
        const menu = document.getElementById(menuId);
        const btn = document.getElementById(menuId.replace('Menu', 'Btn'));
        if (menu) menu.classList.remove('open');
        if (btn) btn.classList.remove('open');
    });
}
function setDeptValue(hiddenId, labelId, value) {
    document.getElementById(hiddenId).value = value || '';
    const label = document.getElementById(labelId);
    if (value) {
        label.textContent = value;
        label.classList.remove('dept-select-placeholder');
    } else {
        label.textContent = 'Select Department';
        label.classList.add('dept-select-placeholder');
    }
}
function selectDept(hiddenId, labelId, menuId, btnId, value) {
    setDeptValue(hiddenId, labelId, value);
    document.getElementById(menuId).classList.remove('open');
    document.getElementById(btnId).classList.remove('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dept-select-wrap')) {
        closeAllDeptMenus();
    }
});
function filterCompTp(inputId, dropdownId, hiddenId) {
    const term = document.getElementById(inputId).value.toLowerCase();
    const dd = document.getElementById(dropdownId);
    if (!term) {
        dd.innerHTML = '';
        dd.classList.remove('open');
        document.getElementById(hiddenId).value = '';
        return;
    }
    const matches = allData.filter(d => (d.TP_Name||'').toLowerCase().includes(term)).slice(0, 5);
    if(matches.length === 0) {
        dd.innerHTML = '<div style="padding:0.5rem;color:var(--muted)">No matches found</div>';
    } else {
        dd.innerHTML = matches.map(d => `<button type="button" class="trainer-flag-reason-item" onclick="selectCompTp('${escapeHtml(d.TP_Name).replace(/'/g, "\\'")}', ${d.TP_ID}, '${inputId}', '${dropdownId}', '${hiddenId}')">${escapeHtml(d.TP_Name)}</button>`).join('');
    }
    dd.classList.add('open');
}
function selectCompTp(name, id, inputId, dropdownId, hiddenId) {
    document.getElementById(inputId).value = name;
    document.getElementById(hiddenId).value = id;
    document.getElementById(dropdownId).classList.remove('open');
}
function submitNewComplaint(e) {
    e.preventDefault();
    const data = {
        date_of_complaint: document.getElementById('compDate').value,
        employee_name: document.getElementById('compEmpName').value,
        employee_id: document.getElementById('compEmpId').value,
        department: document.getElementById('compDept').value,
        learnops: document.getElementById('compLearnOps').value,
        training_provider_id: document.getElementById('compTpId').value,
        complaint_category: document.getElementById('compCategory').value,
        complaint_summary: document.getElementById('compSummary').value,
        priority: document.getElementById('compPriority').value,
        status: 'Open'
    };
    if (!data.department) {
        showToast('Please select a Department'); return;
    }
    if (!data.training_provider_id) {
        showToast('Please select a valid Training Provider'); return;
    }
    fetch('api/submit-complaint.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if(res.success) {
            showToast('✓ Complaint submitted successfully');
            closeComplaintModal();
            loadData();
        } else {
            showToast('⚠️ ' + (res.error || 'Failed to submit'));
        }
    }).catch(console.error);
}
function fetchComplaints() {
    const term = document.getElementById('compSearchInput').value;
    const year = document.getElementById('compFilterYear').value;
    const status = document.getElementById('compFilterStatus').value;
    
    let params = 'search=' + encodeURIComponent(term);
    if (year) params += '&year=' + encodeURIComponent(year);
    if (status) params += '&status=' + encodeURIComponent(status);
    
    fetch('api/get-complaints.php?' + params)
    .then(r => r.json()).then(res => {
        if(res.success) {
            complaintsCache = res.data;
            renderComplaintList();
        }
    }).catch(console.error);
}
function renderComplaintList() {
    const c = document.getElementById('compListContainer');
    if (!complaintsCache.length) {
        c.innerHTML = '<div style="padding:1rem;color:var(--muted);text-align:center;">No complaints found</div>';
        return;
    }
    c.innerHTML = complaintsCache.map(comp => `
        <div style="border:1px solid var(--border);border-radius:6px;padding:0.75rem;margin-bottom:0.5rem;background:var(--paper);cursor:pointer;" onclick="loadEditComplaint('${comp.case_id}')">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                <strong>${escapeHtml(comp.case_id)}</strong>
                <span style="font-size:0.8rem;background:var(--blue-lt);color:var(--blue-dk);padding:2px 6px;border-radius:4px;">${escapeHtml(comp.status)}</span>
            </div>
            <div style="font-size:0.75rem;color:var(--muted);margin-bottom:0.15rem;">📅 ${escapeHtml(comp.date_of_complaint || 'N/A')}</div>
            <div style="font-size:0.85rem;color:var(--muted);margin-bottom:0.25rem;">${escapeHtml(comp.tp_name || 'Unknown TP')}</div>
            <div style="font-size:0.85rem;color:var(--ink);">${escapeHtml(comp.complaint_category)} - ${escapeHtml(comp.priority)} Priority</div>
        </div>
    `).join('');
}
function showComplaintList() {
    document.getElementById('compListSection').style.display = 'block';
    document.getElementById('compEditSection').style.display = 'none';
}
function loadEditComplaint(caseId) {
    const comp = complaintsCache.find(c => c.case_id === caseId);
    if (!comp) return;
    
    document.getElementById('editCompCaseId').textContent = comp.case_id;
    document.getElementById('editCompCaseIdHidden').value = comp.case_id;
    document.getElementById('editCompDate').value = comp.date_of_complaint || '';
    document.getElementById('editCompPriority').value = comp.priority || '';
    document.getElementById('editCompEmpName').value = comp.employee_name || '';
    document.getElementById('editCompEmpId').value = comp.employee_id || '';
    
    // Attempt to set department immediately if loaded, otherwise wait for fetchDepartments
    if (departmentsCache.length > 0) {
        setDeptValue('editCompDept', 'editCompDeptLabel', comp.department || '');
    } else {
        // Just store it, fetchDepartments will set it
        document.getElementById('editCompDept').dataset.pendingValue = comp.department || '';
    }
    
    document.getElementById('editCompLearnOps').value = comp.learnops || '';
    document.getElementById('editCompTpSearch').value = comp.tp_name || '';
    document.getElementById('editCompTpId').value = comp.training_provider_id || '';
    document.getElementById('editCompCategory').value = comp.complaint_category || '';
    document.getElementById('editCompSummary').value = comp.complaint_summary || '';
    
    document.getElementById('editCompStatus').value = comp.status || 'Open';
    document.getElementById('editCompDecision').value = comp.ldcm_decision || 'No Action';
    document.getElementById('editCompDecisionDate').value = comp.decision_date || '';
    document.getElementById('editCompRemarks').value = comp.remarks || '';

    document.getElementById('compListSection').style.display = 'none';
    document.getElementById('compEditSection').style.display = 'block';
}
function submitEditComplaint(e) {
    e.preventDefault();
    const data = {
        case_id: document.getElementById('editCompCaseIdHidden').value,
        date_of_complaint: document.getElementById('editCompDate').value,
        employee_name: document.getElementById('editCompEmpName').value,
        employee_id: document.getElementById('editCompEmpId').value,
        department: document.getElementById('editCompDept').value,
        learnops: document.getElementById('editCompLearnOps').value,
        training_provider_id: document.getElementById('editCompTpId').value,
        complaint_category: document.getElementById('editCompCategory').value,
        complaint_summary: document.getElementById('editCompSummary').value,
        priority: document.getElementById('editCompPriority').value,
        status: document.getElementById('editCompStatus').value,
        ldcm_decision: document.getElementById('editCompDecision').value,
        decision_date: document.getElementById('editCompDecisionDate').value,
        remarks: document.getElementById('editCompRemarks').value
    };
    if (!data.department) {
        showToast('Please select a Department'); return;
    }
    fetch('api/update-complaint.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(res => {
        if(res.success) {
            showToast('✓ Complaint updated successfully');
            fetchComplaints();
            showComplaintList();
            if (data.ldcm_decision === 'Blacklist') loadData();
        } else {
            showToast('⚠️ ' + (res.error || 'Failed to update'));
        }
    }).catch(console.error);
}
</script>

</body>
</html>