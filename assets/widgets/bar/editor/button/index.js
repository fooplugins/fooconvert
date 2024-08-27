"use strict";(self.webpackChunkfooconvert=self.webpackChunkfooconvert||[]).push([[810],{906:function(e,o,n){n.d(o,{q:function(){return h},A:function(){return _}});var t=n(143),l=n(715),s=n(136),i=n(942),c=n.n(i),r=n(908),a=n(677),u=n(790),d=e=>{var o,n,t,s;const{isHidden:i,attributes:{icon:d,styles:v},defaults:b,iconSets:p}=e,f=(0,r.useStyles)(v,{background:"background",icon:"color"}),g=(0,l.useBlockProps)({className:c()("fc--bar-button",{"is-hidden":i}),style:{...f,fontSize:null!==(o=d?.size)&&void 0!==o?o:b?.size}}),h=(0,r.getIconSetsIcon)(p,null!==(n=null!==(t=d?.close?.slug)&&void 0!==t?t:b?.close?.slug)&&void 0!==n?n:"wordpress-reset");return(0,u.jsx)("button",{...g,children:(0,u.jsx)(a.A,{icon:h.svg,size:null!==(s=d?.size)&&void 0!==s?s:b?.size})})},v=n(723),b=n(427),p=n(380),f=e=>{var o,n,t,l,i;const{value:c,onChange:a,iconSets:d,defaults:b={},currentAction:f,panelId:g}=e,h=e=>{const o="object"==typeof e?{...null!=c?c:{},...e}:void 0;a((0,s.A)(o))},_=e=>h({size:e}),x=(e,o,n)=>{let t;if("object"==typeof o){var l;const e=(0,r.renderIconSetIconToString)(o,null!==(l=c?.size)&&void 0!==l?l:b?.icon?.size,n);e&&(t={slug:o.slug,svg:e})}h({[e]:t})},I=(0,r.getIconSetsIcon)(d,null!==(o=null!==(n=c?.close?.slug)&&void 0!==n?n:b?.close?.slug)&&void 0!==o?o:"wordpress-reset"),m=e=>x("close",e,{slot:"button-icon",className:"button-icon button-icon--close"}),j=(0,r.getIconSetsIcon)(d,null!==(t=null!==(l=c?.open?.slug)&&void 0!==l?l:b?.open?.slug)&&void 0!==t?t:"wordpress-create"),C=e=>x("open",e,{slot:"button-icon",className:"button-icon button-icon--open"});return(0,u.jsxs)(r.ToolsPanel,{panelId:g,label:(0,v.__)("Icon","fooconvert"),resetAll:()=>h(void 0),children:[(0,u.jsx)(r.ToolsPanelItem,{panelId:g,label:(0,v.__)("Size","fooconvert"),hasValue:()=>(0,p.A)(c?.size,!0),onDeselect:()=>_(void 0),isShownByDefault:!0,children:(0,u.jsx)(r.SizeControl,{label:(0,v.__)("Size","fooconvert"),value:null!==(i=c?.size)&&void 0!==i?i:b?.size,onChange:_,sizes:[{value:"16px",abbr:(0,v.__)("S","fooconvert"),label:(0,v.__)("Small","fooconvert")},{value:"24px",abbr:(0,v.__)("M","fooconvert"),label:(0,v.__)("Medium","fooconvert")},{value:"32px",abbr:(0,v.__)("L","fooconvert"),label:(0,v.__)("Large","fooconvert")},{value:"48px",abbr:(0,v.__)("XL","fooconvert"),label:(0,v.__)("Extra Large","fooconvert")}],units:[{value:"px",label:"px",default:24,step:4,min:16,max:256},{value:"em",label:"em",default:1,step:.1,min:1,max:16},{value:"rem",label:"rem",default:1,step:.1,min:1,max:16}]})}),(0,u.jsx)(r.ToolsPanelItem,{panelId:g,label:(0,v.__)("Close","fooconvert"),hasValue:()=>(0,p.A)(c?.close?.slug,!0),onDeselect:()=>m(void 0),children:(0,u.jsx)(r.IconPickerControl,{label:(0,v.__)("Close","fooconvert"),value:I,onChange:m,iconSets:d,help:(0,v.__)("The icon displayed when clicking the button will close the bar.","fooconvert")})}),"toggle"===f&&(0,u.jsx)(r.ToolsPanelItem,{panelId:g,label:(0,v.__)("Open","fooconvert"),hasValue:()=>(0,p.A)(c?.open?.slug,!0),onDeselect:()=>C(void 0),children:(0,u.jsx)(r.IconPickerControl,{label:(0,v.__)("Open","fooconvert"),value:j,onChange:C,iconSets:d,help:(0,v.__)("The icon displayed when clicking the button will open the bar.","fooconvert")})})]})},g=e=>{const{clientId:o,attributes:{action:n,icon:t,position:i,styles:c},setAttributes:a,defaults:d,iconSets:p}=e,g=e=>{const o="object"==typeof e?{...null!=c?c:{},...e}:void 0;a({styles:(0,s.A)(o)})},h=[{value:"close",label:(0,v.__)("Close","fooconvert")},{value:"toggle",label:(0,v.__)("Toggle","fooconvert")}],_=[{value:"left",label:(0,v.__)("Left","fooconvert")},{value:"right",label:(0,v.__)("Right","fooconvert")}],x=[{key:"background",label:(0,v.__)("Background","fooconvert"),enableAlpha:!0,enableGradient:!0},{key:"icon",label:(0,v.__)("Icon","fooconvert")}];return(0,u.jsxs)(u.Fragment,{children:[(0,u.jsxs)(l.InspectorControls,{group:"settings",children:[(0,u.jsx)(b.PanelBody,{title:(0,v.__)("Position","fooconvert"),children:(0,u.jsx)(b.PanelRow,{children:(0,u.jsx)(r.ToggleSelectControl,{label:(0,v.__)("Position","fooconvert"),hideLabelFromVision:!0,value:null!=i?i:d?.position,onChange:e=>a({position:e===d?.position?void 0:e}),options:_,help:(0,v.__)("Choose where to display the button within the bar.","fooconvert")})})}),(0,u.jsx)(b.PanelBody,{title:(0,v.__)("Behavior","fooconvert"),children:(0,u.jsx)(b.PanelRow,{children:(0,u.jsx)(r.ToggleSelectControl,{label:(0,v.__)("Action","fooconvert"),value:null!=n?n:d?.action,onChange:e=>a({action:e===d?.action?void 0:e}),options:h,help:(0,v.__)("Choose the action to perform when the button is clicked.","fooconvert")})})}),(0,u.jsx)(f,{panelId:o,value:t,onChange:e=>a({icon:e}),defaults:d?.icon,iconSets:p,currentAction:n})]}),(0,u.jsxs)(l.InspectorControls,{group:"styles",children:[(0,u.jsx)(r.ColorToolsPanel,{value:c?.color,onChange:e=>g({color:e}),panelId:o,options:x}),(0,u.jsx)(r.BorderToolsPanel,{panelId:o,value:c?.border,onChange:e=>g({border:e})}),(0,u.jsx)(r.DimensionToolsPanel,{panelId:o,value:c?.dimensions,onChange:e=>g({dimensions:e}),defaults:d?.styles?.dimensions,controls:["margin","padding"]})]})]})};const h={action:"close",position:"right",styles:{dimensions:{padding:"16px",margin:"16px"}},icon:{size:"32px",close:{slug:"wordpress-reset"},open:{slug:"wordpress-create"}}};var _=e=>{const{context:{"fc-bar/clientId":o,"fc-bar/button":n={},"fc-bar/hideButton":i}}=e,c=(0,r.useIconSets)(),{updateBlockAttributes:a}=(0,t.useDispatch)(l.store),{attributes:v,setAttributes:b,...p}=e,f={...p,isHidden:i,parentClientId:o,attributes:n,setAttributes:e=>{"string"==typeof o&&a(o,{button:(0,s.A)({...n,...e})},!1)},defaults:{...h},iconSets:c};return i?null:(0,u.jsxs)(u.Fragment,{children:[(0,u.jsx)(d,{...f}),(0,u.jsx)(g,{...f})]})}},915:function(e,o,n){var t=n(997),l=n(19),s=JSON.parse('{"UU":"fc/bar-button"}'),i=n(906);(0,t.registerBlockType)(s.UU,{icon:l.A,edit:i.A,save:()=>null})},908:function(e){e.exports=window.FooConvert.editor}},function(e){e(e.s=915)}]);