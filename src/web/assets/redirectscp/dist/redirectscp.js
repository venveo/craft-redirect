!function(){var e={887:function(){function e(t){return e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},e(t)}"undefined"===e(Craft.Redirects)&&(Craft.Redirects={}),function(e){Craft.Redirects.AdminTableSiteSwitcher=Garnish.Base.extend({$siteMenuButton:null,adminTableVm:null,siteMenu:null,siteId:null,init:function(t,n,i){if(this.$siteMenuBtn=e(t).find(".sitemenubtn:first"),this.$siteMenuBtn.length){this.adminTableVm=n,this.siteMenu=this.$siteMenuBtn.menubtn().data("menubtn").menu;var r=this.siteMenu.$options.filter(".sel:first");r.length||(r=this.siteMenu.$options.first()),this.siteMenu.on("optionselect",e.proxy(this,"_handleSiteChange")),this.trigger("afterInit")}},_handleSiteChange:function(t){this.siteMenu.$options.removeClass("sel");var n=e(t.selectedOption).addClass("sel");this.$siteMenuBtn.html(n.html()),this._setSite(n.data("site-id"))},_setSite:function(e){var t=this.adminTableVm.$children[0].$props.tableDataEndpoint;this.adminTableVm.$children[0].$props.tableDataEndpoint=Craft.getActionUrl("vredirect/catch-all/hits-table?siteId="+Craft.siteId);var n="",i=t.split("?"),r=i[0],s=i[1],o="";if(s){i=s.split("&");for(var a=0;a<i.length;a++)"siteId"!=i[a].split("=")[0]&&(n+=o+i[a],o="&")}var d=r+"?"+n+o+"siteId="+e;this.adminTableVm.$children[0].$props.tableDataEndpoint=d}})}(jQuery)},941:function(){function e(t){return e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},e(t)}"undefined"===e(Craft.Redirects)&&(Craft.Redirects={}),Craft.Redirects.CatchAllIndex=Garnish.Base.extend({adminTableVm:null,init:function(e){var t=this;this.adminTableVm=e,$(document.body).on("click",".createRedirectBtn",(function(e){t._createRedirect(e.target.dataset.id)}))},_createRedirect:function(e){var t=this;Craft.sendActionRequest("POST","elements/create",{data:{elementType:"venveo\\redirect\\elements\\Redirect"}}).then((function(e){Craft.createElementEditor("venveo\\redirect\\elements\\Redirect",{siteId:t.siteId,elementId:e.data.element.id,draftId:e.data.element.draftId,params:{fresh:1}}).on("submit",(function(){t.selectElementAfterUpdate(e.data.element.id),t.updateElements()}))})).finally((function(){}))}})},524:function(){function e(t){return e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},e(t)}"undefined"===e(Craft.Redirects)&&(Craft.Redirects={}),Craft.Redirects.ElementRedirectSlideout=Garnish.Base.extend({elementId:null,siteId:null,$openDialogButton:null,init:function(e,t,n){var i=this;void 0===n&&$.isPlainObject(e)&&(n=e,e=null),this.elementId=e,this.siteId=t,this.setSettings(n,Craft.ElementRedirectSlideout.defaults),this.$openDialogButton=$("#redirect-slideout-trigger > button"),this.addListener(this.$openDialogButton,"click",(function(e){e.preventDefault();var t=axios.CancelToken.source();Craft.sendActionRequest("GET",Craft.getActionUrl("vredirect/element-slideouts/get-element-view-html",{elementId:i.elementId,siteId:i.siteId}),{cancelToken:t.token}).then((function(e){t=null,new Craft.Slideout(e.data)})),Garnish.$win.on("beforeunload",(function(){t&&t.cancel()}))}))}})},249:function(){Craft.Redirects.RedirectsIndex=Craft.BaseElementIndex.extend({$newRedirectBtn:null,forceCreateInSlideout:!0,init:function(e,t,n){this.on("selectSource",this.updateButton.bind(this)),this.on("selectSite",this.updateButton.bind(this)),this.base(e,t,n)},updateButton:function(){var e=this;this.$newRedirectBtn&&this.$newRedirectBtn.remove(),this.$newRedirectBtn=Craft.ui.createButton({label:Craft.t("vredirect","New redirect"),spinner:!0}).addClass("submit add icon btngroup-btn-last"),this.addListener(this.$newRedirectBtn,"click mousedown",(function(){var t=e.$source.data("id");e._createRedirect(t)})),this.addButton(this.$newRedirectBtn)},_createRedirect:function(e){var t=this;this.$newRedirectBtn.hasClass("loading")||(this.$newRedirectBtn.addClass("loading"),Craft.sendActionRequest("POST","vredirect/redirects/create",{data:{siteId:this.siteId,group:e}}).then((function(e){var n=e.data;t.forceCreateInSlideout||"index"!==t.settings.context?Craft.createElementEditor(t.elementType,{siteId:t.siteId,elementId:n.redirect.id,draftId:n.redirect.draftId,params:{fresh:1}}).on("submit",(function(){t.clearSearch(),t.setSelectedSortAttribute("dateCreated","desc"),t.selectElementAfterUpdate(n.entry.id),t.updateElements()})):document.location.href=Craft.getUrl(n.cpEditUrl,{fresh:1})})).finally((function(){t.$newRedirectBtn.removeClass("loading")})))}}),Craft.registerElementIndexClass("venveo\\redirect\\elements\\Redirect",Craft.Redirects.RedirectsIndex)},101:function(){function e(t){return e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},e(t)}"undefined"===e(Craft.Redirects)&&(Craft.Redirects={}),function(e){Craft.Redirects.UrlFieldInput=Garnish.Base.extend({$container:null,siteOptions:[],$siteSelect:null,$textInput:null,$prefixContainer:null,selectedSite:null,init:function(t,n){this.$container=e(t),this.setSettings(n,Craft.Redirects.UrlFieldInput.defaults),this.$siteSelect=this.$container.find(".sites > select"),this.$textInput=this.$container.find(".url"),this.$prefixContainer=this.$container.find(".prefix"),this.siteOptions=this.settings.siteOptions,this.addListener(this.$siteSelect,"change",(function(t){var n=e(t.target)[0].value;this.changeSite(n)})),this.changeSite(this.$siteSelect[0].value),this.trigger("afterInit")},changeSite:function(e){this.selectedSite=null;for(var t=0;t<this.siteOptions.length;t++)this.siteOptions[t].id==e&&(this.selectedSite=this.siteOptions[t]);if(!this.selectedSite)return this.$prefixContainer.empty(),void this.$prefixContainer.hide();this.$prefixContainer.show(),this.$prefixContainer.text(this.selectedSite.baseUrl),console.log(this.selectedSite)}},{defaults:{}})}(jQuery)},466:function(){},973:function(e,t,n){var i=n(466);i.__esModule&&(i=i.default),"string"==typeof i&&(i=[[e.id,i,""]]),i.locals&&(e.exports=i.locals),(0,n(673).Z)("550c889c",i,!0,{})},673:function(e,t,n){"use strict";function i(e,t){for(var n=[],i={},r=0;r<t.length;r++){var s=t[r],o=s[0],a={id:e+":"+r,css:s[1],media:s[2],sourceMap:s[3]};i[o]?i[o].parts.push(a):n.push(i[o]={id:o,parts:[a]})}return n}n.d(t,{Z:function(){return p}});var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var s={},o=r&&(document.head||document.getElementsByTagName("head")[0]),a=null,d=0,l=!1,c=function(){},u=null,f="data-vue-ssr-id",h="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function p(e,t,n,r){l=n,u=r||{};var o=i(e,t);return m(o),function(t){for(var n=[],r=0;r<o.length;r++){var a=o[r];(d=s[a.id]).refs--,n.push(d)}for(t?m(o=i(e,t)):o=[],r=0;r<n.length;r++){var d;if(0===(d=n[r]).refs){for(var l=0;l<d.parts.length;l++)d.parts[l]();delete s[d.id]}}}}function m(e){for(var t=0;t<e.length;t++){var n=e[t],i=s[n.id];if(i){i.refs++;for(var r=0;r<i.parts.length;r++)i.parts[r](n.parts[r]);for(;r<n.parts.length;r++)i.parts.push(b(n.parts[r]));i.parts.length>n.parts.length&&(i.parts.length=n.parts.length)}else{var o=[];for(r=0;r<n.parts.length;r++)o.push(b(n.parts[r]));s[n.id]={id:n.id,refs:1,parts:o}}}}function y(){var e=document.createElement("style");return e.type="text/css",o.appendChild(e),e}function b(e){var t,n,i=document.querySelector("style["+f+'~="'+e.id+'"]');if(i){if(l)return c;i.parentNode.removeChild(i)}if(h){var r=d++;i=a||(a=y()),t=C.bind(null,i,r,!1),n=C.bind(null,i,r,!0)}else i=y(),t=g.bind(null,i),n=function(){i.parentNode.removeChild(i)};return t(e),function(i){if(i){if(i.css===e.css&&i.media===e.media&&i.sourceMap===e.sourceMap)return;t(e=i)}else n()}}var v,S=(v=[],function(e,t){return v[e]=t,v.filter(Boolean).join("\n")});function C(e,t,n,i){var r=n?"":i.css;if(e.styleSheet)e.styleSheet.cssText=S(t,r);else{var s=document.createTextNode(r),o=e.childNodes;o[t]&&e.removeChild(o[t]),o.length?e.insertBefore(s,o[t]):e.appendChild(s)}}function g(e,t){var n=t.css,i=t.media,r=t.sourceMap;if(i&&e.setAttribute("media",i),u.ssrId&&e.setAttribute(f,t.id),r&&(n+="\n/*# sourceURL="+r.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}}},t={};function n(i){var r=t[i];if(void 0!==r)return r.exports;var s=t[i]={id:i,exports:{}};return e[i](s,s.exports,n),s.exports}n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,{a:t}),t},n.d=function(e,t){for(var i in t)n.o(t,i)&&!n.o(e,i)&&Object.defineProperty(e,i,{enumerable:!0,get:t[i]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){"use strict";function e(t){return e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},e(t)}n(973),n(887),n(941),n(524),n(249),n(101),jQuery,"undefined"===e(Craft.Redirects)&&(Craft.Redirects={})}()}();
//# sourceMappingURL=redirectscp.js.map