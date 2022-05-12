/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed


if (typeof Craft.Redirects === typeof undefined) {
    Craft.Redirects = {};
}
Craft.Redirects.RedirectsIndex = Craft.BaseElementIndex.extend({
    elementType: 'venveo\\redirect\\elements\\Redirect',
    $newRedirectBtn: null,

    init: function (elementType, $container, settings) {
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },


    updateButton: function () {
        // Remove the old button, if there is one
        if (this.$newRedirectBtn) {
            this.$newRedirectBtn.remove();
        }

        this.$newRedirectBtn = Craft.ui.createButton({
            label: Craft.t('vredirect', 'New redirect'),
            spinner: true,
        })
            .addClass('submit add icon btngroup-btn-last');

        this.addListener(this.$newRedirectBtn, 'click', () => {
            this._createRedirect();
        });

        this.addButton(this.$newRedirectBtn);
    },

    _createRedirect: function () {
        if (this.$newRedirectBtn.hasClass('loading')) {
            return;
        }

        this.$newRedirectBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: this.elementType,
                siteId: this.siteId,
            },
        }).then(ev => {
            const slideout = Craft.createElementEditor(this.elementType, {
                siteId: this.siteId,
                elementId: ev.data.element.id,
                draftId: ev.data.element.draftId,
                params: {
                    fresh: 1,
                },
            });
            slideout.on('submit', () => {
                this.selectElementAfterUpdate(ev.data.element.id);
                this.updateElements();
            });
        }).finally(() => {
            this.$newRedirectBtn.removeClass('loading');
        });
    },
});

Craft.registerElementIndexClass('venveo\\redirect\\elements\\Redirect', Craft.Redirects.RedirectsIndex);
