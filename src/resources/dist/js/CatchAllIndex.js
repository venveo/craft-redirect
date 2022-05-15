
/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed


if (typeof Craft.Redirects === typeof undefined) {
    Craft.Redirects = {};
}
Craft.Redirects.CatchAllIndex = Garnish.Base.extend({
    adminTableVm: null,
    init: function(adminTableVm) {
        this.adminTableVm = adminTableVm
        $(document.body).on('click', '.createRedirectBtn', (ev) => {
            this._createRedirect(ev.target.dataset.id);
        });
    },
    _createRedirect: function (catchAllId) {
        Craft.sendActionRequest('POST', 'elements/create', {
            data: {
                elementType: 'venveo\\redirect\\elements\\Redirect',
            },
        }).then(ev => {
            const slideout = Craft.createElementEditor('venveo\\redirect\\elements\\Redirect', {
                siteId: this.siteId,
                elementId: ev.data.element.id,
                draftId: ev.data.element.draftId,
                params: {
                    fresh: 1
                },
            });
            slideout.on('submit', () => {
                this.selectElementAfterUpdate(ev.data.element.id);
                this.updateElements();
            });
        }).finally(() => {
            // this.$newRedirectBtn.removeClass('loading');
        });
    },
});
