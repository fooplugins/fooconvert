!function(){"use strict";var e,t={950:function(e,t,r){r.d(t,{A:function(){return i}});const i=e=>Object.prototype.toString.call(e)},944:function(e,t,r){r.d(t,{A:function(){return n}});var i=r(981);const n=e=>{return!(t=e,null===t||(0,i.A)(e));var t}},981:function(e,t,r){r.d(t,{A:function(){return i}});const i=e=>void 0===e||void 0===e}},r={};function i(e){var n=r[e];if(void 0!==n)return n.exports;var o=r[e]={id:e,exports:{}};return t[e](o,o.exports,i),o.exports}i.m=t,e=[],i.O=function(t,r,n,o){if(!r){var s=1/0;for(g=0;g<e.length;g++){r=e[g][0],n=e[g][1],o=e[g][2];for(var c=!0,a=0;a<r.length;a++)(!1&o||s>=o)&&Object.keys(i.O).every((function(e){return i.O[e](r[a])}))?r.splice(a--,1):(c=!1,o<s&&(s=o));if(c){e.splice(g--,1);var l=n();void 0!==l&&(t=l)}}return t}o=o||0;for(var g=e.length;g>0&&e[g-1][2]>o;g--)e[g]=e[g-1];e[g]=[r,n,o]},i.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return i.d(t,{a:t}),t},i.d=function(e,t){for(var r in t)i.o(t,r)&&!i.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},i.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"==typeof window)return window}}(),i.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},i.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},function(){var e={343:0};i.O.j=function(t){return 0===e[t]};var t=function(t,r){var n,o,s=r[0],c=r[1],a=r[2],l=0;if(s.some((function(t){return 0!==e[t]}))){for(n in c)i.o(c,n)&&(i.m[n]=c[n]);if(a)var g=a(i)}for(t&&t(r);l<s.length;l++)o=s[l],i.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return i.O(g)},r=self.webpackChunkfooconvert=self.webpackChunkfooconvert||[];r.forEach(t.bind(null,0)),r.push=t.bind(null,r.push.bind(r))}();var n={},o={};i.r(o),i.d(o,{CustomElement:function(){return h},TriggeredElement:function(){return E}});var s={};i.r(s),i.d(s,{getDocumentScrollPercent:function(){return b},getElementConfiguration:function(){return u},hasAdBlock:function(){return p}});var c=i(950),a=i(944);const l=function(e){let t=arguments.length>1&&void 0!==arguments[1]&&arguments[1];return(0,a.A)(e)&&("string"==typeof e||"[object String]"===(0,c.A)(e))&&(!t||t&&e.trim().length>0)},g=e=>(0,a.A)(e)&&("[object Function]"===(0,c.A)(e)||"function"==typeof e||e instanceof Function);var u=e=>{if(l(e)){const t=e.replaceAll(/\W/g,"_").replace(/^(\d)/,"$$1").toUpperCase();if(Object.hasOwn(globalThis,t))return globalThis[t]}};class d extends HTMLElement{constructor(){super()}#e={};#t=!1;#r=null;get config(){return this.#e}get isInitialized(){return this.#t}connectedCallback(){this.isInitialized||(this.#e=u(this.id),this.initialize(),this.#t=!0),this.connected(),this.#r=requestAnimationFrame((()=>{this.#r=null,this.connectedNextFrame()}))}disconnectedCallback(){null!==this.#r&&cancelAnimationFrame(this.#r),this.disconnected()}initialize(){}connected(){}connectedNextFrame(){}disconnected(){}dispatch(e,t){if(l(e,!0))return function(e){let t=arguments.length>1&&void 0!==arguments[1]&&arguments[1],r=arguments.length>2?arguments[2]:void 0;if((0,a.A)(e)){const i=Object.getPrototypeOf(e);if(null===i||i.constructor===Object){const i=Object.entries(e);if(!t||t&&i.length>0)return!g(r)||i.every((t=>{let[i,n]=t;return r(n,i,e)}))}}return!1}(t)&&Object.hasOwn(t,"detail")?this.dispatchEvent(new CustomEvent(e,t)):this.dispatchEvent(new Event(e));throw new DOMException("Failed to execute 'dispatch' on 'CustomElement': parameter 1 is not of type 'string' or is empty.","InvalidStateError")}}var h=d,f=i(981);const m=e=>(0,a.A)(e)&&"[object Number]"===(0,c.A)(e)&&!isNaN(e),v=(e,t)=>l(e)&&(l(t)||t instanceof RegExp)?e.split(t).map((e=>e.trim())).filter(Boolean):[];var b=()=>i.g.scrollY/(i.g.document.documentElement.scrollHeight-i.g.document.documentElement.clientHeight)*100;let T=null;var p=async()=>{if(null===T){T=!1;try{await i.g.fetch(`https://ads.google.com?=${(new Date).getTime()}`,{mode:"no-cors",method:"HEAD"}).catch((e=>{T=!0}))}catch{T=!0}}return T};class y extends h{static#i=["immediate","adblock","anchor","scroll","timer","visible","exit-intent"];static get triggerTypes(){return y.#i}static isTriggerType(e){return y.triggerTypes.includes(e)}constructor(){super()}get trigger(){const e=this.config.trigger;return this.constructor.isTriggerType(e)?e:null}get triggerData(){const e=this.config.triggerData;if(!(0,f.A)(e))switch(this.trigger){case"anchor":case"visible":return l(e,!0)?e:null;case"adblock":case"exit-intent":case"timer":case"scroll":return m(e)?e:null}return null}connected(){super.connected(),this.connectTrigger()}disconnected(){super.disconnected(),this.disconnectTrigger()}triggeredCallback(e,...t){}#n=null;connectTrigger(){switch(this.disconnectTrigger(),this.trigger){case"immediate":this.#n=this.initImmediateTrigger();break;case"anchor":this.#n=this.initAnchorTrigger(this.triggerData);break;case"adblock":this.#n=this.initAdBlockTrigger(this.triggerData);break;case"exit-intent":this.#n=this.initExitIntentTrigger(this.triggerData);break;case"scroll":this.#n=this.initScrollTrigger(this.triggerData);break;case"timer":this.#n=this.initTimerTrigger(this.triggerData);break;case"visible":this.#n=this.initVisibleTrigger(this.triggerData);break;default:this.#n=null}}disconnectTrigger(){g(this.#n)&&(this.#n(),this.#n=null)}initImmediateTrigger(){const e=requestAnimationFrame((()=>{this.triggeredCallback("immediate")}));return()=>{cancelAnimationFrame(e)}}initAnchorTrigger(e){if(l(e,!0)){const t=e=>{e.preventDefault(),this.triggeredCallback("anchor",e.target)},r=[];return v(e,",").forEach((e=>{const i=this.ownerDocument.getElementById(e);i instanceof HTMLElement&&(i.addEventListener("click",t),r.push(i))})),()=>{r.forEach((e=>e.removeEventListener("click",t)))}}}initAdBlockTrigger(e){if(m(e)){const t=()=>{b()>e&&(this.ownerDocument.removeEventListener("scroll",t),this.triggeredCallback("adblock",e))},r=()=>{this.ownerDocument.removeEventListener("scroll",t)};return p().then((i=>{console.log("adblock",i,this.isConnected,this.#n===r),i&&this.isConnected&&this.#n===r&&(b()>e?this.triggeredCallback("adblock",e):this.ownerDocument.addEventListener("scroll",t,{passive:!0}))})),r}}initExitIntentTrigger(e){if(m(e)){const t=e=>{e.clientY<0&&(this.ownerDocument.body.removeEventListener("mouseleave",t),this.triggeredCallback("exit-intent"))},r=()=>{this.ownerDocument.body.addEventListener("mouseleave",t,{passive:!0})},i=()=>{this.ownerDocument.body.removeEventListener("mouseleave",t)};if(e>0){const t=setTimeout(r,1e3*e);return()=>{clearTimeout(t),i()}}return r(),i}}initTimerTrigger(e){if(m(e)){const t=setTimeout((()=>{this.triggeredCallback("timer",e)}),1e3*e);return()=>{clearTimeout(t)}}}initScrollTrigger(e){if(m(e)){if(!(b()>e)){const t=()=>{b()>e&&(this.ownerDocument.removeEventListener("scroll",t),this.triggeredCallback("scroll",e))};return this.ownerDocument.addEventListener("scroll",t,{passive:!0}),()=>{this.ownerDocument.removeEventListener("scroll",t)}}this.triggeredCallback("scroll",e)}}initVisibleTrigger(e){if(l(e,!0)){const t=v(e,",").reduce(((e,t)=>{const r=this.ownerDocument.getElementById(t);return r instanceof HTMLElement&&e.push(r),e}),[]);if(t.length>0){const e=new i.g.IntersectionObserver((t=>{const r=t.find((e=>e.isIntersecting));r&&(e.disconnect(),this.triggeredCallback("visible",r.target))}),{root:this.ownerDocument});return t.forEach((t=>e.observe(t))),()=>{e.disconnect()}}}}}var E=y;globalThis?.FooConvert||(globalThis.FooConvert={}),globalThis.FooConvert={...globalThis.FooConvert,...s,...o},n=i.O(n)}();