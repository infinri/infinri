(function () {
    'use strict';

    const InfinriTabs = {
        init() {
            const tabLists = document.querySelectorAll('[role="tablist"]');
            tabLists.forEach(tabList => this.initTabList(tabList));
        },

        initTabList(tabList) {
            const tabs = tabList.querySelectorAll('[role="tab"]');

            tabs.forEach((tab, index) => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.selectTab(tab);
                });

                tab.addEventListener('keydown', (e) => {
                    this.handleKeyboard(e, tabs, index);
                });

                if (index === 0 && !tabList.querySelector('[aria-selected="true"]')) {
                    this.selectTab(tab);
                }
            });
        },

        selectTab(tab) {
            const tabList = tab.closest('[role="tablist"]');
            const tabs = tabList.querySelectorAll('[role="tab"]');
            const targetId = tab.getAttribute('aria-controls');
            const targetPanel = document.getElementById(targetId);

            if (!targetPanel) return;

            tabs.forEach(t => {
                t.setAttribute('aria-selected', 'false');
                t.setAttribute('tabindex', '-1');
            });

            document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
                panel.hidden = true;
            });

            tab.setAttribute('aria-selected', 'true');
            tab.setAttribute('tabindex', '0');
            tab.focus();
            targetPanel.hidden = false;

            tab.dispatchEvent(new CustomEvent('tab:select', {
                bubbles: true,
                detail: {tab, panel: targetPanel}
            }));
        },

        handleKeyboard(e, tabs, currentIndex) {
            let targetIndex;

            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    targetIndex = currentIndex - 1;
                    if (targetIndex < 0) targetIndex = tabs.length - 1;
                    this.selectTab(tabs[targetIndex]);
                    break;

                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    targetIndex = currentIndex + 1;
                    if (targetIndex >= tabs.length) targetIndex = 0;
                    this.selectTab(tabs[targetIndex]);
                    break;

                case 'Home':
                    e.preventDefault();
                    this.selectTab(tabs[0]);
                    break;

                case 'End':
                    e.preventDefault();
                    this.selectTab(tabs[tabs.length - 1]);
                    break;
            }
        }
    };

    window.InfinriTabs = InfinriTabs;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfinriTabs.init());
    } else {
        InfinriTabs.init();
    }
})();
