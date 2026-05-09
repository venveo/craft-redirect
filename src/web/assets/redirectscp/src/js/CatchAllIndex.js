/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed
if (typeof Craft.Redirects === typeof undefined) {
  Craft.Redirects = {};
}
Craft.Redirects.CatchAllIndex = Garnish.Base.extend({
  adminTableVm: null,
  $container: null,
  init: function (adminTableVm, settings) {
    this.adminTableVm = adminTableVm;
    this.setSettings(settings, Craft.Redirects.CatchAllIndex.defaults);
    this.$container = $(this.settings.container || document.body);
    this.$container.on("click", ".createRedirectBtn", (ev) => {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      this._createRedirect(
        ev.currentTarget.dataset.id,
        ev.currentTarget.dataset.siteId
      );
    });
  },
  _createRedirect: function (catchAllId, siteId) {
    siteId = siteId || this.settings.siteId || Craft.siteId;
    Craft.sendActionRequest("POST", "vredirect/redirects/create", {
      data: {
        siteId: siteId,
        catchAllId: catchAllId,
      },
    })
      .then(({ data }) => {
        const slideout = Craft.createElementEditor(this.settings.elementType, {
          siteId: siteId,
          elementId: data.redirect.id,
          draftId: data.redirect.draftId,
          params: {
            fresh: 1,
          },
        });
        slideout.on("submit", () => {
          if (
            this.adminTableVm &&
            this.adminTableVm.$children &&
            this.adminTableVm.$children[0]
          ) {
            this.adminTableVm.$children[0].reload();
          }
        });
      });
  },
}, {
  defaults: {
    container: null,
    siteId: null,
    elementType: "venveo\\redirect\\elements\\Redirect",
  },
});
