(function () {
    const getComponent = () => {
        const BX = window.BX;

        return BX && BX.Sale ? BX.Sale.OrderAjaxComponent : null;
    };

    const isVisibleSection = (component, section) => {
        if (!section || !section.classList.contains('bx-active')) {
            return false;
        }

        if (section === component.pickUpBlockNode && section.style.display === 'none') {
            return false;
        }

        return section.style.display !== 'none';
    };

    const expandSection = (component, section, method) => {
        if (!isVisibleSection(component, section) || section.dataset.signaExpanded === '1') {
            return;
        }

        if (component.activeSectionId !== section.id) {
            const hidden = document.getElementById(section.id + '-hidden');

            if (hidden && hidden.firstElementChild && typeof component[method] === 'function') {
                component[method](true);
            }
        }

        section.dataset.signaExpanded = '1';
    };

    const expand = () => {
        const component = getComponent();

        if (!component || !component.orderBlockNode) {
            return;
        }

        if (component.result && component.result.SHOW_AUTH) {
            return;
        }

        const sections = component.orderBlockNode.querySelectorAll('.bx-soa-section.bx-active');

        sections.forEach((section) => {
            section.style.height = '';
            section.classList.remove('bx-step-completed');

            const content = section.querySelector('.bx-soa-section-content');

            if (content) {
                content.style.display = '';
            }

            const title = section.querySelector('.bx-soa-section-title-container');

            if (title) {
                title.style.pointerEvents = 'none';
            }

            section.querySelectorAll('.bx-soa-editstep').forEach((node) => {
                node.style.display = 'none';
            });
        });

        expandSection(component, component.regionBlockNode, 'editActiveRegionBlock');
        expandSection(component, component.propsBlockNode, 'editActivePropsBlock');
        expandSection(component, component.deliveryBlockNode, 'editActiveDeliveryBlock');
        expandSection(component, component.pickUpBlockNode, 'editActivePickUpBlock');
        expandSection(component, component.paySystemBlockNode, 'editActivePaySystemBlock');
        expandSection(component, component.basketBlockNode, 'editActiveBasketBlock');

        component.orderBlockNode.querySelectorAll('.bx-soa-more').forEach((node) => {
            node.remove();
        });

        if (typeof component.alignBasketColumns === 'function') {
            component.alignBasketColumns();
        }
    };

    let expandTimer = null;

    const scheduleExpand = () => {
        window.clearTimeout(expandTimer);
        expandTimer = window.setTimeout(expand, 0);
    };

    const patch = () => {
        const component = getComponent();

        if (!component || component.__signaCheckoutPatch) {
            return false;
        }

        component.__signaCheckoutPatch = true;

        const originalEditOrder = component.editOrder;

        if (typeof originalEditOrder === 'function') {
            component.editOrder = function () {
                const result = originalEditOrder.apply(this, arguments);

                if (this.orderBlockNode) {
                    this.orderBlockNode.querySelectorAll('.bx-soa-section').forEach((node) => {
                        delete node.dataset.signaExpanded;
                    });
                }

                scheduleExpand();

                return result;
            };
        }

        const originalLocationsCompletion = component.locationsCompletion;

        if (typeof originalLocationsCompletion === 'function') {
            component.locationsCompletion = function () {
                const result = originalLocationsCompletion.apply(this, arguments);

                scheduleExpand();

                return result;
            };
        }

        component.changeVisibleSection = function (section) {
            if (!section) {
                return;
            }

            const content = section.querySelector('.bx-soa-section-content');

            if (content) {
                content.style.display = '';
            }

            const editStep = section.querySelector('.bx-soa-editstep');

            if (editStep) {
                editStep.style.display = 'none';
            }

            const title = section.querySelector('.bx-soa-section-title-container');

            if (title) {
                title.style.pointerEvents = 'none';
            }
        };

        component.editFadeRegionContent = function () {};
        component.editFadePropsContent = function () {};

        return true;
    };

    const boot = () => {
        patch();
        expand();
    };

    if (getComponent()) {
        patch();
    }

    if (window.BX && typeof window.BX.ready === 'function') {
        window.BX.ready(boot);
    } else {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    }

    let componentRegistered = false;

    const registerComponent = () => {
        if (componentRegistered || !window.signa) {
            return;
        }

        componentRegistered = true;

        window.signa.component('checkout-page', () => {
            if (window.BX && typeof window.BX.ready === 'function') {
                window.BX.ready(boot);
            } else {
                window.setTimeout(boot, 0);
            }
        });
    };

    registerComponent();

    if (window.BX && typeof window.BX.ready === 'function') {
        window.BX.ready(registerComponent);
    } else {
        document.addEventListener('DOMContentLoaded', registerComponent, { once: true });
    }

    window.addEventListener('load', registerComponent, { once: true });
})();