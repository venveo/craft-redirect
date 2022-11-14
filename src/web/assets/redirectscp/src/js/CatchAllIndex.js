/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed

Craft.Redirects.CatchAllIndex = Garnish.Base.extend({
  adminTableVm: null,
  init: function (adminTableVm) {
    this.adminTableVm = adminTableVm;
    $(document.body).on("click", ".createRedirectBtn", (ev) => {
      this._createRedirect(ev.target.dataset.id);
    });
  },
  _createRedirect: function (catchAllId) {
    Craft.sendActionRequest("POST", "vredirect/redirects/create", {
      data: {
        siteId: this.siteId,
        catchAllId: catchAllId,
      },
    })
      .then(({ data }) => {
        const slideout = Craft.createElementEditor(this.elementType, {
          siteId: this.siteId,
          elementId: data.redirect.id,
          draftId: data.redirect.draftId,
          params: {
            fresh: 1,
          },
        });
        slideout.on("submit", () => {
          this.adminTableVm.$children[0].reload();
        });
      })
      .finally(() => {});
  },
});
