/* ============================================================
   Interactive Product Tour
   Lightweight, dependency-free walkthrough of the tenant app.
   Driven entirely by DOM steps that target elements already on
   the Dashboard page (via [data-tour="..."] attributes) or, for
   intro/outro screens, a centered modal with no target.
   ============================================================ */

(function () {
    'use strict';

    var body = document.body;
    var cfg = {
        scope:        body.dataset.tourScope || 'anon',
        company:      body.dataset.tourCompany || 'the system',
        userName:     body.dataset.tourUser || '',
        isDashboard:  body.dataset.tourPage === 'dashboard',
        dashboardUrl: body.dataset.tourDashboardUrl || '',
        docsUrl:      body.dataset.tourDocsUrl || '',
        supportUrl:   body.dataset.tourSupportUrl || '',
    };

    var STORAGE_KEY = 'payroll_tour_state_v1';

    function readState() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        } catch (e) {
            return {};
        }
    }

    function hasSeenTour() {
        return !!readState()[cfg.scope];
    }

    function markTourSeen(status) {
        var state = readState();
        state[cfg.scope] = status;
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) { /* localStorage unavailable — tour just re-shows next visit, harmless */ }
    }

    function firstMatch(selectors) {
        if (!selectors) {
            return null;
        }
        var list = Array.isArray(selectors) ? selectors : [selectors];
        for (var i = 0; i < list.length; i++) {
            var el = document.querySelector(list[i]);
            if (el) {
                return el;
            }
        }
        return null;
    }

    function greetingName() {
        var name = cfg.userName ? cfg.userName.split(' ')[0] : 'there';
        return escapeHtml(name);
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /** Only render a link if it uses a scheme we trust — a superadmin
     * field is still an untrusted string as far as innerHTML is concerned. */
    function safeUrl(url) {
        if (!/^(https?:|mailto:|tel:)/i.test(url)) {
            return null;
        }
        return escapeHtml(url);
    }

    function buildSteps() {
        return [
            {
                id: 'welcome',
                icon: '👋',
                title: 'Welcome to ' + cfg.company + '!',
                body: '<p>Hi ' + greetingName() + ', this is your Payroll Management System. ' +
                      'Take a two-minute tour to see how everything fits together, or skip it and explore on your own.</p>',
            },
            {
                id: 'dashboard-overview',
                target: '[data-tour="stat-cards"]',
                title: 'Dashboard Overview',
                body: '<p>Your dashboard gives you a live snapshot of the business: active employees, ' +
                      "today's attendance, and payroll activity, all in one place.</p>",
            },
            {
                id: 'navigation',
                target: '#sidebar',
                title: 'Navigation Tour',
                body: '<p>Everything lives in the sidebar, grouped by purpose: People, Time &amp; Pay, ' +
                      'Finance, Analytics, and (for admins) System settings.</p>',
            },
            {
                id: 'core-modules',
                title: 'Core Modules',
                body: '<div class="tour-module-grid">' +
                      '<div class="tour-module"><i class="fa fa-users"></i><span><strong>Employees</strong><br>Manage records &amp; profiles</span></div>' +
                      '<div class="tour-module"><i class="fa fa-calendar-check"></i><span><strong>Attendance</strong><br>Track time in/out daily</span></div>' +
                      '<div class="tour-module"><i class="fa fa-money-bill-wave"></i><span><strong>Payroll</strong><br>Generate &amp; finalize pay runs</span></div>' +
                      '<div class="tour-module"><i class="fa fa-circle-minus"></i><span><strong>Deductions</strong><br>Loans &amp; recurring deductions</span></div>' +
                      '<div class="tour-module"><i class="fa fa-hand-holding-heart"></i><span><strong>Benefits</strong><br>SSS, PhilHealth, Pag-IBIG</span></div>' +
                      '<div class="tour-module"><i class="fa fa-chart-bar"></i><span><strong>Reports</strong><br>Export &amp; analyze data</span></div>' +
                      '</div>',
            },
            {
                id: 'first-task',
                target: ['[data-tour="first-task-add-employee"]', '[data-tour="nav-employees"]'],
                title: 'Your First Task',
                body: "<p>A good place to start: add your first employee. Every payroll and attendance record begins here.</p>",
            },
            {
                id: 'advanced',
                target: '[data-tour="advanced-features"]',
                title: 'Advanced Features',
                body: '<p>As an admin, use <strong>Control Panel</strong> to manage departments, branches, ' +
                      'roles, and branding, and check <strong>Audit Logs</strong> to review every change made in the system.</p>',
            },
            {
                id: 'tips',
                icon: '💡',
                title: 'Tips & Best Practices',
                body: '<ul>' +
                      '<li>Set up <strong>Departments</strong> and <strong>Branches</strong> first, under Control Panel.</li>' +
                      '<li>Create <strong>Users</strong> with the right role so staff only see what they need.</li>' +
                      '<li>Record <strong>Attendance</strong> before generating payroll for accurate computations.</li>' +
                      '<li>Review <strong>Audit Logs</strong> periodically to keep track of account activity.</li>' +
                      '</ul>',
            },
            {
                id: 'completion',
                icon: '🎉',
                title: "You're ready!",
                body: buildCompletionBody(),
                completion: true,
            },
        ];
    }

    function buildCompletionBody() {
        var html = '<p>That\'s the whole tour. You know your way around now, go run your payroll.</p>';
        var links = '';
        var docsUrl = safeUrl(cfg.docsUrl);
        var supportUrl = safeUrl(cfg.supportUrl);
        if (docsUrl) {
            links += '<a href="' + docsUrl + '" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">' +
                     '<i class="fa fa-book me-1"></i>View Documentation</a>';
        }
        if (supportUrl) {
            links += '<a href="' + supportUrl + '" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">' +
                     '<i class="fa fa-headset me-1"></i>Contact Support</a>';
        }
        if (links) {
            html += '<div class="tour-links">' + links + '</div>';
        }
        html += '<p class="text-muted small mb-0">You can restart this tour anytime from the <strong>Tour</strong> button in the top bar.</p>';
        return html;
    }

    var steps = [];
    var current = -1;
    var els = {};
    var lastFocused = null;
    var raf = null;

    function isVisible(el) {
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function resolveStepTarget(step) {
        if (!step.target) {
            return null;
        }
        var el = firstMatch(step.target);
        return el && isVisible(el) ? el : null;
    }

    function buildDom() {
        els.clickblock = document.createElement('div');
        els.clickblock.className = 'tour-clickblock';

        els.highlight = document.createElement('div');
        els.highlight.className = 'tour-highlight';
        els.highlight.style.display = 'none';

        els.tooltip = document.createElement('div');
        els.tooltip.className = 'tour-tooltip';
        els.tooltip.setAttribute('role', 'dialog');
        els.tooltip.setAttribute('aria-modal', 'true');
        els.tooltip.setAttribute('tabindex', '-1');
        els.tooltip.innerHTML =
            '<button type="button" class="tour-close-x" aria-label="Close tour"><i class="fa fa-xmark"></i></button>' +
            '<div class="tour-progress" id="tour-progress"></div>' +
            '<div id="tour-icon"></div>' +
            '<h3 id="tour-title"></h3>' +
            '<div class="tour-body" id="tour-body"></div>' +
            '<div class="tour-footer">' +
            '  <button type="button" class="tour-skip-link" id="tour-skip">Skip tour</button>' +
            '  <div class="tour-nav-btns">' +
            '    <button type="button" class="btn btn-sm btn-outline-secondary" id="tour-prev">Back</button>' +
            '    <button type="button" class="btn btn-sm btn-primary" id="tour-next">Next</button>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(els.clickblock);
        document.body.appendChild(els.highlight);
        document.body.appendChild(els.tooltip);

        els.tooltip.querySelector('.tour-close-x').addEventListener('click', function () { closeTour('skipped'); });
        els.tooltip.querySelector('#tour-skip').addEventListener('click', function () { closeTour('skipped'); });
        els.tooltip.querySelector('#tour-prev').addEventListener('click', goPrev);
        els.tooltip.querySelector('#tour-next').addEventListener('click', goNext);

        document.addEventListener('keydown', onKeydown);
        window.addEventListener('resize', onViewportChange);
        window.addEventListener('scroll', onViewportChange, true);
    }

    function onViewportChange() {
        if (raf) {
            return;
        }
        raf = requestAnimationFrame(function () {
            raf = null;
            if (current >= 0) {
                renderStep(current, true);
            }
        });
    }

    function onKeydown(e) {
        if (current < 0) {
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            closeTour('skipped');
            return;
        }
        if (e.key === 'Tab') {
            trapFocus(e);
        }
    }

    function trapFocus(e) {
        var focusable = els.tooltip.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) {
            return;
        }
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function renderStep(index, isReflow) {
        var step = steps[index];
        var targetEl = resolveStepTarget(step);

        if (step.target && !targetEl) {
            if (isReflow) {
                // Target vanished after a resize/scroll (e.g. sidebar
                // collapsed) — leave the last rendered position alone
                // rather than reprocessing the same step (would recurse).
                return;
            }
            // Target not on the page for this user/role — skip it silently.
            advanceSkipping(index, lastDirection || 1);
            return;
        }

        var progress = els.tooltip.querySelector('#tour-progress');
        progress.textContent = 'Step ' + (index + 1) + ' of ' + steps.length;

        var iconEl = els.tooltip.querySelector('#tour-icon');
        if (step.icon) {
            iconEl.className = 'tour-tooltip-icon';
            iconEl.textContent = step.icon;
            iconEl.style.display = '';
        } else {
            iconEl.style.display = 'none';
        }

        els.tooltip.querySelector('#tour-title').textContent = step.title;
        els.tooltip.querySelector('#tour-body').innerHTML = step.body;

        var prevBtn = els.tooltip.querySelector('#tour-prev');
        var nextBtn = els.tooltip.querySelector('#tour-next');
        prevBtn.style.visibility = index === 0 ? 'hidden' : 'visible';
        nextBtn.textContent = step.completion ? 'Finish' : 'Next';

        if (targetEl) {
            targetEl.scrollIntoView({ block: 'center', behavior: isReflow ? 'auto' : 'smooth' });
            var rect = targetEl.getBoundingClientRect();
            var pad = 8;
            els.highlight.style.display = 'block';
            els.highlight.style.top = Math.max(rect.top - pad, 4) + 'px';
            els.highlight.style.left = Math.max(rect.left - pad, 4) + 'px';
            els.highlight.style.width = (rect.width + pad * 2) + 'px';
            els.highlight.style.height = (rect.height + pad * 2) + 'px';
            els.clickblock.classList.remove('is-modal-step');
            els.tooltip.classList.remove('tour-modal');
            positionTooltipNear(rect);
        } else {
            els.highlight.style.display = 'none';
            els.clickblock.classList.add('is-modal-step');
            els.tooltip.classList.add('tour-modal');
            els.tooltip.style.top = '';
            els.tooltip.style.left = '';
        }

        if (!isReflow) {
            els.tooltip.focus();
        }
    }

    var lastDirection = 1;

    function advanceSkipping(index, direction) {
        var next = index + (direction || 1);
        if (next < 0 || next >= steps.length) {
            closeTour(direction < 0 ? null : 'completed');
            return;
        }
        current = next;
        renderStep(current);
    }

    function positionTooltipNear(rect) {
        var tt = els.tooltip;
        tt.style.transform = 'none';
        var margin = 14;
        var ttRect = tt.getBoundingClientRect();
        var top = rect.bottom + margin;

        if (top + ttRect.height > window.innerHeight - 10) {
            top = rect.top - ttRect.height - margin;
        }
        if (top < 10) {
            top = Math.min(Math.max(rect.top, 10), window.innerHeight - ttRect.height - 10);
        }

        var left = rect.left;
        if (left + ttRect.width > window.innerWidth - 10) {
            left = window.innerWidth - ttRect.width - 10;
        }
        if (left < 10) {
            left = 10;
        }

        tt.style.top = top + 'px';
        tt.style.left = left + 'px';
    }

    function goNext() {
        lastDirection = 1;
        if (steps[current].completion) {
            closeTour('completed');
            return;
        }
        advanceSkipping(current, 1);
    }

    function goPrev() {
        lastDirection = -1;
        if (current === 0) {
            return;
        }
        advanceSkipping(current, -1);
    }

    function startTour() {
        if (!cfg.isDashboard) {
            var url = cfg.dashboardUrl + (cfg.dashboardUrl.indexOf('?') > -1 ? '&' : '?') + 'tour=1';
            window.location.href = url;
            return;
        }

        steps = buildSteps();
        lastFocused = document.activeElement;

        if (!els.tooltip) {
            buildDom();
        }

        current = 0;
        document.body.style.overflow = 'hidden';
        renderStep(current);
    }

    function closeTour(status) {
        if (current < 0) {
            return;
        }
        current = -1;
        document.body.style.overflow = '';
        if (els.clickblock) { els.clickblock.remove(); }
        if (els.highlight) { els.highlight.remove(); }
        if (els.tooltip) { els.tooltip.remove(); }
        els = {};

        if (status) {
            markTourSeen(status);
        }
        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('tour-start-btn');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                startTour();
            });
        }

        if (!cfg.isDashboard) {
            return;
        }

        var params = new URLSearchParams(window.location.search);
        if (params.get('tour') === '1') {
            params.delete('tour');
            var clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', clean);
            setTimeout(startTour, 300);
        } else if (!hasSeenTour()) {
            setTimeout(startTour, 800);
        }
    });
})();
