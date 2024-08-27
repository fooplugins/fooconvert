"use strict";(self.webpackChunkfooconvert=self.webpackChunkfooconvert||[]).push([[470],{813:function(t,n,o){var e=o(215),i=o(38),s=o(950),r=o(944);const a=new CSSStyleSheet;a.replaceSync(i.A);const h=document.createElement("template");h.innerHTML='<div part="container">\r\n    <button part="button" type="button" tabindex="0">\r\n        <slot name="button-icon" part="button-icon">\r\n            <svg class="button-icon button-icon--close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32px" height="32px" aria-hidden="true" focusable="false">\r\n                <path d="M7 11.5h10V13H7z"></path>\r\n            </svg>\r\n            <svg class="button-icon button-icon--open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">\r\n                <path d="M16 11.2h-3.2V8h-1.6v3.2H8v1.6h3.2V16h1.6v-3.2H16z"></path>\r\n            </svg>\r\n        </slot>\r\n    </button>\r\n    <div part="content">\r\n        <slot></slot>\r\n    </div>\r\n</div>';class l extends e.TriggeredElement{static get observedAttributes(){return["open"]}constructor(){super(),this.attachShadow({mode:"open"}).append(h.content.cloneNode(!0)),this.shadowRoot.adoptedStyleSheets.push(a),this.#t=this.shadowRoot.querySelector("[part~=container]"),this.#n=this.shadowRoot.querySelector("[part~=button]"),this.#o=this.shadowRoot.querySelector("[part~=content]"),this.onButtonClicked=this.onButtonClicked.bind(this),this.onCloseTriggered=this.onCloseTriggered.bind(this)}#t=null;get containerElement(){return this.#t}#n=null;get buttonElement(){return this.#n}#o=null;get contentElement(){return this.#o}updateButtonPosition(){const t=this.buttonPosition;if(["left","right"].includes(t)){const{marginRight:n,marginLeft:o}=getComputedStyle(this.buttonElement),{paddingRight:e,paddingLeft:i}=getComputedStyle(this.contentElement),s=`calc(${this.buttonElement.offsetWidth}px + ${o} + ${n})`,r="right"===t?"padding-right":"padding-left",a="right"===t?e:i;this.contentElement.style.setProperty(r,`calc(${a} + ${s})`,"important")}}initialize(){this.hasAttribute("tabindex")||this.setAttribute("tabindex","0"),this.setAttribute("role","dialog")}connected(){super.connected(),this.updateButtonPosition(),this.pagePush&&(this.updatePagePush(),this.transitions&&document.documentElement.classList.add("fc-bar__page-push-transition")),this.buttonElement.addEventListener("click",this.onButtonClicked)}disconnected(){super.disconnected(),this.pagePush&&(document.documentElement.classList.remove("fc-bar__page-push-transition"),this.updatePagePush(!1)),this.buttonElement.removeEventListener("click",this.onButtonClicked)}triggeredCallback(t,...n){this.open=!0}onButtonClicked(){this.buttonIsToggle?this.open=!this.open:this.open=!1}onCloseTriggered(){this.open=!1}get open(){return this.hasAttribute("open")}set open(t){this.toggleAttribute("open",Boolean(t))}get transitions(){return this.hasAttribute("transitions")}get pagePush(){return this.hasAttribute("page-push")}get position(){return this.hasAttribute("bottom")?"bottom":"top"}get buttonPosition(){return this.hasAttribute("button-none")?"none":this.hasAttribute("button-left")?"left":"right"}get buttonIsToggle(){return this.hasAttribute("button-toggle")}attributeChangedCallback(t,n,o){"open"===t&&this.#e(this.open)}#e(t){const n=t?"open":"close";this.pagePush&&this.updatePagePush(t),this.dispatch(n)}updatePagePush(t){var n;(!0===(n=t)||!1===n||(0,r.A)(n)&&"[object Boolean]"===(0,s.A)(n)?t:this.open)?this.#i():this.#s()}#i(){const t=document.documentElement,n=`${this.id}__page-push`;if(!t.classList.contains(n)){t.classList.add(n);const o=getComputedStyle(t),e=parseInt(o.getPropertyValue(`margin-${this.position}`));if(!isNaN(e)){const n=`${e+this.offsetHeight}px`;t.style.setProperty(`margin-${this.position}`,n,"important"),t.style.setProperty(`scroll-padding-${this.position}`,n)}}}#s(){const t=document.documentElement,n=`${this.id}__page-push`;if(t.classList.contains(n)){t.classList.remove(n);const o=getComputedStyle(t),e=parseInt(o.getPropertyValue(`margin-${this.position}`));if(isNaN(e))t.style.removeProperty(`margin-${this.position}`),t.style.removeProperty(`scroll-padding-${this.position}`);else{let n="bottom"===this.position?0:parseInt(o.getPropertyValue("--wp-admin--admin-bar--height"));isNaN(n)&&(n=t.body?.classList?.contains("admin-bar")?32:0);const i=e-this.offsetHeight;n===i?(t.style.removeProperty(`margin-${this.position}`),t.style.removeProperty(`scroll-padding-${this.position}`)):(t.style.setProperty(`margin-${this.position}`,`${i}px`,"important"),t.style.setProperty(`scroll-padding-${this.position}`,`${i}px`))}}}}var c=l;globalThis.customElements&&globalThis.customElements.define("fc-bar",c),globalThis.FooConvert={...globalThis.FooConvert,BarElement:c}},38:function(t,n,o){var e=o(601),i=o.n(e),s=o(314),r=o.n(s)()(i());r.push([t.id,":host{box-sizing:border-box;position:fixed;top:var(--wp-admin--admin-bar--height, 0px);left:0;right:0;bottom:unset;display:none;flex-direction:row;justify-content:center;align-items:center;width:100%;margin:0;max-width:100% !important;height:fit-content;z-index:99998;pointer-events:none;color:#000}:host::part(container){box-sizing:border-box;position:relative;display:block;width:100%;height:fit-content;min-width:200px;min-height:40px;z-index:2}:host::part(button){pointer-events:auto;box-sizing:border-box;position:absolute;top:0;right:0;display:flex;flex-direction:row;justify-content:center;align-items:center;width:fit-content;height:fit-content;background:none;color:inherit;font-size:32px;padding:16px;margin:16px;cursor:pointer;z-index:2;border:none;outline:none}:host::part(button):hover{opacity:.9}:host::part(button):hover,:host::part(button):focus,:host::part(button):active{border:inherit;outline:inherit}:host::part(content){pointer-events:auto;display:flex;flex-direction:row;flex-wrap:nowrap;align-items:center;justify-content:center;box-sizing:border-box;z-index:1;width:100%;height:fit-content;overflow:hidden;color:inherit;background:#fff;margin:0;padding:16px}:host([bottom]){top:unset;bottom:0}:host([bottom])::part(button){top:unset;bottom:0}:host([button-left])::part(button){right:unset;left:0}:host .button-icon,:host ::slotted(svg.button-icon){display:inline-block;width:1em;height:1em;stroke-width:0;stroke:currentColor;fill:currentColor}:host .button-icon--open,:host ::slotted(svg.button-icon--open){display:none}:host([button-toggle]:not([open])) .button-icon--close,:host([button-toggle]:not([open])) ::slotted(svg.button-icon--close){display:none}:host([button-toggle]:not([open])) .button-icon--open,:host([button-toggle]:not([open])) ::slotted(svg.button-icon--open){display:inline-block}:host([open]),:host([button-toggle]){display:flex}:host([button-toggle])::part(content){visibility:hidden;opacity:0;transform:translateY(-100%)}:host([button-toggle][open])::part(content){visibility:visible;opacity:1;transform:translateY(0%)}:host([button-toggle][bottom])::part(content){transform:translateY(100%)}:host([button-toggle][bottom][open])::part(content){transform:translateY(0%)}:host([button-none])::part(button){display:none}:host([transitions])::part(content){transition-property:transform,visibility,opacity;transition-duration:.3s;transition-timing-function:ease-in-out}",""]),n.A=r.toString()},314:function(t){t.exports=function(t){var n=[];return n.toString=function(){return this.map((function(n){var o="",e=void 0!==n[5];return n[4]&&(o+="@supports (".concat(n[4],") {")),n[2]&&(o+="@media ".concat(n[2]," {")),e&&(o+="@layer".concat(n[5].length>0?" ".concat(n[5]):""," {")),o+=t(n),e&&(o+="}"),n[2]&&(o+="}"),n[4]&&(o+="}"),o})).join("")},n.i=function(t,o,e,i,s){"string"==typeof t&&(t=[[null,t,void 0]]);var r={};if(e)for(var a=0;a<this.length;a++){var h=this[a][0];null!=h&&(r[h]=!0)}for(var l=0;l<t.length;l++){var c=[].concat(t[l]);e&&r[c[0]]||(void 0!==s&&(void 0===c[5]||(c[1]="@layer".concat(c[5].length>0?" ".concat(c[5]):""," {").concat(c[1],"}")),c[5]=s),o&&(c[2]?(c[1]="@media ".concat(c[2]," {").concat(c[1],"}"),c[2]=o):c[2]=o),i&&(c[4]?(c[1]="@supports (".concat(c[4],") {").concat(c[1],"}"),c[4]=i):c[4]="".concat(i)),n.push(c))}},n}},601:function(t){t.exports=function(t){return t[1]}},215:function(t){t.exports=window.FooConvert}},function(t){t(t.s=813)}]);