import{Annotation as Qe,StateEffect as I,EditorSelection as v,codePointAt as C,codePointSize as O,fromCodePoint as ce,Facet as fe,combineConfig as Xe,StateField as X,Prec as Y,Text as Ye,Transaction as Ge,MapMode as G,RangeValue as Je,RangeSet as Ze,CharCategory as J}from"@codemirror/state";import{Direction as _e,logException as Z,showTooltip as et,EditorView as R,ViewPlugin as tt,getTooltip as he,Decoration as U,WidgetType as it,keymap as pe}from"@codemirror/view";import{syntaxTree as B,indentUnit as nt}from"@codemirror/language";class _{constructor(e,t,n,o){this.state=e,this.pos=t,this.explicit=n,this.view=o,this.abortListeners=[],this.abortOnDocChange=!1}tokenBefore(e){let t=B(this.state).resolveInner(this.pos,-1);for(;t&&e.indexOf(t.name)<0;)t=t.parent;return t?{from:t.from,to:this.pos,text:this.state.sliceDoc(t.from,this.pos),type:t.type}:null}matchBefore(e){let t=this.state.doc.lineAt(this.pos),n=Math.max(t.from,this.pos-250),o=t.text.slice(n-t.from,this.pos-t.from),s=o.search(ge(e,!1));return s<0?null:{from:n+s,to:this.pos,text:o.slice(s)}}get aborted(){return this.abortListeners==null}addEventListener(e,t,n){e=="abort"&&this.abortListeners&&(this.abortListeners.push(t),n&&n.onDocChange&&(this.abortOnDocChange=!0))}}function ue(i){let e=Object.keys(i).join(""),t=/\w/.test(e);return t&&(e=e.replace(/\w/g,"")),`[${t?"\\w":""}${e.replace(/[^\w\s]/g,"\\$&")}]`}function ot(i){let e=Object.create(null),t=Object.create(null);for(let{label:o}of i){e[o[0]]=!0;for(let s=1;s<o.length;s++)t[o[s]]=!0}let n=ue(e)+ue(t)+"*$";return[new RegExp("^"+n),new RegExp(n)]}function de(i){let e=i.map(o=>typeof o=="string"?{label:o}:o),[t,n]=e.every(o=>/^\w+$/.test(o.label))?[/\w*$/,/\w+$/]:ot(e);return o=>{let s=o.matchBefore(n);return s||o.explicit?{from:s?s.from:o.pos,options:e,validFor:t}:null}}function st(i,e){return t=>{for(let n=B(t.state).resolveInner(t.pos,-1);n;n=n.parent){if(i.indexOf(n.name)>-1)return e(t);if(n.type.isTop)break}return null}}function lt(i,e){return t=>{for(let n=B(t.state).resolveInner(t.pos,-1);n;n=n.parent){if(i.indexOf(n.name)>-1)return null;if(n.type.isTop)break}return e(t)}}class me{constructor(e,t,n,o){this.completion=e,this.source=t,this.match=n,this.score=o}}function T(i){return i.selection.main.from}function ge(i,e){var t;let{source:n}=i,o=e&&n[0]!="^",s=n[n.length-1]!="$";return!o&&!s?i:new RegExp(`${o?"^":""}(?:${n})${s?"$":""}`,(t=i.flags)!==null&&t!==void 0?t:i.ignoreCase?"i":"")}const V=Qe.define();function ye(i,e,t,n){let{main:o}=i.selection,s=t-o.from,l=n-o.from;return Object.assign(Object.assign({},i.changeByRange(r=>r!=o&&t!=n&&i.sliceDoc(r.from+s,r.from+l)!=i.sliceDoc(t,n)?{range:r}:{changes:{from:r.from+s,to:n==o.from?r.to:r.from+l,insert:e},range:v.cursor(r.from+s+e.length)})),{scrollIntoView:!0,userEvent:"input.complete"})}const be=new WeakMap;function rt(i){if(!Array.isArray(i))return i;let e=be.get(i);return e||be.set(i,e=de(i)),e}const H=I.define(),F=I.define();class at{constructor(e){this.pattern=e,this.chars=[],this.folded=[],this.any=[],this.precise=[],this.byWord=[],this.score=0,this.matched=[];for(let t=0;t<e.length;){let n=C(e,t),o=O(n);this.chars.push(n);let s=e.slice(t,t+o),l=s.toUpperCase();this.folded.push(C(l==s?s.toLowerCase():l,0)),t+=o}this.astral=e.length!=this.chars.length}ret(e,t){return this.score=e,this.matched=t,this}match(e){if(this.pattern.length==0)return this.ret(-100,[]);if(e.length<this.pattern.length)return null;let{chars:t,folded:n,any:o,precise:s,byWord:l}=this;if(t.length==1){let d=C(e,0),A=O(d),S=A==e.length?0:-100;if(d!=t[0])if(d==n[0])S+=-200;else return null;return this.ret(S,[0,A])}let r=e.indexOf(this.pattern);if(r==0)return this.ret(e.length==this.pattern.length?0:-100,[0,this.pattern.length]);let a=t.length,c=0;if(r<0){for(let d=0,A=Math.min(e.length,200);d<A&&c<a;){let S=C(e,d);(S==t[c]||S==n[c])&&(o[c++]=d),d+=O(S)}if(c<a)return null}let f=0,h=0,p=!1,u=0,g=-1,x=-1,K=/[a-z]/.test(e),k=!0;for(let d=0,A=Math.min(e.length,200),S=0;d<A&&h<a;){let b=C(e,d);r<0&&(f<a&&b==t[f]&&(s[f++]=d),u<a&&(b==t[u]||b==n[u]?(u==0&&(g=d),x=d+1,u++):u=0));let N,Q=b<255?b>=48&&b<=57||b>=97&&b<=122?2:b>=65&&b<=90?1:0:(N=ce(b))!=N.toLowerCase()?1:N!=N.toUpperCase()?2:0;(!d||Q==1&&K||S==0&&Q!=0)&&(t[h]==b||n[h]==b&&(p=!0)?l[h++]=d:l.length&&(k=!1)),S=Q,d+=O(b)}return h==a&&l[0]==0&&k?this.result(-100+(p?-200:0),l,e):u==a&&g==0?this.ret(-200-e.length+(x==e.length?0:-100),[0,x]):r>-1?this.ret(-700-e.length,[r,r+this.pattern.length]):u==a?this.ret(-900-e.length,[g,x]):h==a?this.result(-100+(p?-200:0)+-700+(k?0:-1100),l,e):t.length==2?null:this.result((o[0]?-700:0)+-200+-1100,o,e)}result(e,t,n){let o=[],s=0;for(let l of t){let r=l+(this.astral?O(C(n,l)):1);s&&o[s-1]==l?o[s-1]=r:(o[s++]=l,o[s++]=r)}return this.ret(e-n.length,o)}}class ct{constructor(e){this.pattern=e,this.matched=[],this.score=0,this.folded=e.toLowerCase()}match(e){if(e.length<this.pattern.length)return null;let t=e.slice(0,this.pattern.length),n=t==this.pattern?0:t.toLowerCase()==this.folded?-200:null;return n==null?null:(this.matched=[0,t.length],this.score=n+(e.length==this.pattern.length?0:-100),this)}}const y=fe.define({combine(i){return Xe(i,{activateOnTyping:!0,activateOnCompletion:()=>!1,activateOnTypingDelay:100,selectOnOpen:!0,override:null,closeOnBlur:!0,maxRenderedOptions:100,defaultKeymap:!0,tooltipClass:()=>"",optionClass:()=>"",aboveCursor:!1,icons:!0,addToOptions:[],positionInfo:ft,filterStrict:!1,compareCompletions:(e,t)=>e.label.localeCompare(t.label),interactionDelay:75,updateSyncTime:100},{defaultKeymap:(e,t)=>e&&t,closeOnBlur:(e,t)=>e&&t,icons:(e,t)=>e&&t,tooltipClass:(e,t)=>n=>xe(e(n),t(n)),optionClass:(e,t)=>n=>xe(e(n),t(n)),addToOptions:(e,t)=>e.concat(t),filterStrict:(e,t)=>e||t})}});function xe(i,e){return i?e?i+" "+e:i:e}function ft(i,e,t,n,o,s){let l=i.textDirection==_e.RTL,r=l,a=!1,c="top",f,h,p=e.left-o.left,u=o.right-e.right,g=n.right-n.left,x=n.bottom-n.top;if(r&&p<Math.min(g,u)?r=!1:!r&&u<Math.min(g,p)&&(r=!0),g<=(r?p:u))f=Math.max(o.top,Math.min(t.top,o.bottom-x))-e.top,h=Math.min(400,r?p:u);else{a=!0,h=Math.min(400,(l?e.right:o.right-e.left)-30);let d=o.bottom-e.bottom;d>=x||d>e.top?f=t.bottom-e.top:(c="bottom",f=e.bottom-t.top)}let K=(e.bottom-e.top)/s.offsetHeight,k=(e.right-e.left)/s.offsetWidth;return{style:`${c}: ${f/K}px; max-width: ${h/k}px`,class:"cm-completionInfo-"+(a?l?"left-narrow":"right-narrow":r?"left":"right")}}function ht(i){let e=i.addToOptions.slice();return i.icons&&e.push({render(t){let n=document.createElement("div");return n.classList.add("cm-completionIcon"),t.type&&n.classList.add(...t.type.split(/\s+/g).map(o=>"cm-completionIcon-"+o)),n.setAttribute("aria-hidden","true"),n},position:20}),e.push({render(t,n,o,s){let l=document.createElement("span");l.className="cm-completionLabel";let r=t.displayLabel||t.label,a=0;for(let c=0;c<s.length;){let f=s[c++],h=s[c++];f>a&&l.appendChild(document.createTextNode(r.slice(a,f)));let p=l.appendChild(document.createElement("span"));p.appendChild(document.createTextNode(r.slice(f,h))),p.className="cm-completionMatchedText",a=h}return a<r.length&&l.appendChild(document.createTextNode(r.slice(a))),l},position:50},{render(t){if(!t.detail)return null;let n=document.createElement("span");return n.className="cm-completionDetail",n.textContent=t.detail,n},position:80}),e.sort((t,n)=>t.position-n.position).map(t=>t.render)}function ee(i,e,t){if(i<=t)return{from:0,to:i};if(e<0&&(e=0),e<=i>>1){let o=Math.floor(e/t);return{from:o*t,to:(o+1)*t}}let n=Math.floor((i-e)/t);return{from:i-(n+1)*t,to:i-n*t}}class pt{constructor(e,t,n){this.view=e,this.stateField=t,this.applyCompletion=n,this.info=null,this.infoDestroy=null,this.placeInfoReq={read:()=>this.measureInfo(),write:a=>this.placeInfo(a),key:this},this.space=null,this.currentClass="";let o=e.state.field(t),{options:s,selected:l}=o.open,r=e.state.facet(y);this.optionContent=ht(r),this.optionClass=r.optionClass,this.tooltipClass=r.tooltipClass,this.range=ee(s.length,l,r.maxRenderedOptions),this.dom=document.createElement("div"),this.dom.className="cm-tooltip-autocomplete",this.updateTooltipClass(e.state),this.dom.addEventListener("mousedown",a=>{let{options:c}=e.state.field(t).open;for(let f=a.target,h;f&&f!=this.dom;f=f.parentNode)if(f.nodeName=="LI"&&(h=/-(\d+)$/.exec(f.id))&&+h[1]<c.length){this.applyCompletion(e,c[+h[1]]),a.preventDefault();return}}),this.dom.addEventListener("focusout",a=>{let c=e.state.field(this.stateField,!1);c&&c.tooltip&&e.state.facet(y).closeOnBlur&&a.relatedTarget!=e.contentDOM&&e.dispatch({effects:F.of(null)})}),this.showOptions(s,o.id)}mount(){this.updateSel()}showOptions(e,t){this.list&&this.list.remove(),this.list=this.dom.appendChild(this.createListBox(e,t,this.range)),this.list.addEventListener("scroll",()=>{this.info&&this.view.requestMeasure(this.placeInfoReq)})}update(e){var t;let n=e.state.field(this.stateField),o=e.startState.field(this.stateField);if(this.updateTooltipClass(e.state),n!=o){let{options:s,selected:l,disabled:r}=n.open;(!o.open||o.open.options!=s)&&(this.range=ee(s.length,l,e.state.facet(y).maxRenderedOptions),this.showOptions(s,n.id)),this.updateSel(),r!=((t=o.open)===null||t===void 0?void 0:t.disabled)&&this.dom.classList.toggle("cm-tooltip-autocomplete-disabled",!!r)}}updateTooltipClass(e){let t=this.tooltipClass(e);if(t!=this.currentClass){for(let n of this.currentClass.split(" "))n&&this.dom.classList.remove(n);for(let n of t.split(" "))n&&this.dom.classList.add(n);this.currentClass=t}}positioned(e){this.space=e,this.info&&this.view.requestMeasure(this.placeInfoReq)}updateSel(){let e=this.view.state.field(this.stateField),t=e.open;if((t.selected>-1&&t.selected<this.range.from||t.selected>=this.range.to)&&(this.range=ee(t.options.length,t.selected,this.view.state.facet(y).maxRenderedOptions),this.showOptions(t.options,e.id)),this.updateSelectedOption(t.selected)){this.destroyInfo();let{completion:n}=t.options[t.selected],{info:o}=n;if(!o)return;let s=typeof o=="string"?document.createTextNode(o):o(n);if(!s)return;"then"in s?s.then(l=>{l&&this.view.state.field(this.stateField,!1)==e&&this.addInfoPane(l,n)}).catch(l=>Z(this.view.state,l,"completion info")):this.addInfoPane(s,n)}}addInfoPane(e,t){this.destroyInfo();let n=this.info=document.createElement("div");if(n.className="cm-tooltip cm-completionInfo",e.nodeType!=null)n.appendChild(e),this.infoDestroy=null;else{let{dom:o,destroy:s}=e;n.appendChild(o),this.infoDestroy=s||null}this.dom.appendChild(n),this.view.requestMeasure(this.placeInfoReq)}updateSelectedOption(e){let t=null;for(let n=this.list.firstChild,o=this.range.from;n;n=n.nextSibling,o++)n.nodeName!="LI"||!n.id?o--:o==e?n.hasAttribute("aria-selected")||(n.setAttribute("aria-selected","true"),t=n):n.hasAttribute("aria-selected")&&n.removeAttribute("aria-selected");return t&&dt(this.list,t),t}measureInfo(){let e=this.dom.querySelector("[aria-selected]");if(!e||!this.info)return null;let t=this.dom.getBoundingClientRect(),n=this.info.getBoundingClientRect(),o=e.getBoundingClientRect(),s=this.space;if(!s){let l=this.dom.ownerDocument.defaultView||window;s={left:0,top:0,right:l.innerWidth,bottom:l.innerHeight}}return o.top>Math.min(s.bottom,t.bottom)-10||o.bottom<Math.max(s.top,t.top)+10?null:this.view.state.facet(y).positionInfo(this.view,t,o,n,s,this.dom)}placeInfo(e){this.info&&(e?(e.style&&(this.info.style.cssText=e.style),this.info.className="cm-tooltip cm-completionInfo "+(e.class||"")):this.info.style.cssText="top: -1e6px")}createListBox(e,t,n){const o=document.createElement("ul");o.id=t,o.setAttribute("role","listbox"),o.setAttribute("aria-expanded","true"),o.setAttribute("aria-label",this.view.state.phrase("Completions"));let s=null;for(let l=n.from;l<n.to;l++){let{completion:r,match:a}=e[l],{section:c}=r;if(c){let p=typeof c=="string"?c:c.name;if(p!=s&&(l>n.from||n.from==0))if(s=p,typeof c!="string"&&c.header)o.appendChild(c.header(c));else{let u=o.appendChild(document.createElement("completion-section"));u.textContent=p}}const f=o.appendChild(document.createElement("li"));f.id=t+"-"+l,f.setAttribute("role","option");let h=this.optionClass(r);h&&(f.className=h);for(let p of this.optionContent){let u=p(r,this.view.state,this.view,a);u&&f.appendChild(u)}}return n.from&&o.classList.add("cm-completionListIncompleteTop"),n.to<e.length&&o.classList.add("cm-completionListIncompleteBottom"),o}destroyInfo(){this.info&&(this.infoDestroy&&this.infoDestroy(),this.info.remove(),this.info=null)}destroy(){this.destroyInfo()}}function ut(i,e){return t=>new pt(t,i,e)}function dt(i,e){let t=i.getBoundingClientRect(),n=e.getBoundingClientRect(),o=t.height/i.offsetHeight;n.top<t.top?i.scrollTop-=(t.top-n.top)/o:n.bottom>t.bottom&&(i.scrollTop+=(n.bottom-t.bottom)/o)}function we(i){return(i.boost||0)*100+(i.apply?10:0)+(i.info?5:0)+(i.type?1:0)}function mt(i,e){let t=[],n=null,o=c=>{t.push(c);let{section:f}=c.completion;if(f){n||(n=[]);let h=typeof f=="string"?f:f.name;n.some(p=>p.name==h)||n.push(typeof f=="string"?{name:h}:f)}},s=e.facet(y);for(let c of i)if(c.hasResult()){let f=c.result.getMatch;if(c.result.filter===!1)for(let h of c.result.options)o(new me(h,c.source,f?f(h):[],1e9-t.length));else{let h=e.sliceDoc(c.from,c.to),p,u=s.filterStrict?new ct(h):new at(h);for(let g of c.result.options)if(p=u.match(g.label)){let x=g.displayLabel?f?f(g,p.matched):[]:p.matched;o(new me(g,c.source,x,p.score+(g.boost||0)))}}}if(n){let c=Object.create(null),f=0,h=(p,u)=>{var g,x;return((g=p.rank)!==null&&g!==void 0?g:1e9)-((x=u.rank)!==null&&x!==void 0?x:1e9)||(p.name<u.name?-1:1)};for(let p of n.sort(h))f-=1e5,c[p.name]=f;for(let p of t){let{section:u}=p.completion;u&&(p.score+=c[typeof u=="string"?u:u.name])}}let l=[],r=null,a=s.compareCompletions;for(let c of t.sort((f,h)=>h.score-f.score||a(f.completion,h.completion))){let f=c.completion;!r||r.label!=f.label||r.detail!=f.detail||r.type!=null&&f.type!=null&&r.type!=f.type||r.apply!=f.apply||r.boost!=f.boost?l.push(c):we(c.completion)>we(r)&&(l[l.length-1]=c),r=c.completion}return l}class D{constructor(e,t,n,o,s,l){this.options=e,this.attrs=t,this.tooltip=n,this.timestamp=o,this.selected=s,this.disabled=l}setSelected(e,t){return e==this.selected||e>=this.options.length?this:new D(this.options,ve(t,e),this.tooltip,this.timestamp,e,this.disabled)}static build(e,t,n,o,s){let l=mt(e,t);if(!l.length)return o&&e.some(a=>a.state==1)?new D(o.options,o.attrs,o.tooltip,o.timestamp,o.selected,!0):null;let r=t.facet(y).selectOnOpen?0:-1;if(o&&o.selected!=r&&o.selected!=-1){let a=o.options[o.selected].completion;for(let c=0;c<l.length;c++)if(l[c].completion==a){r=c;break}}return new D(l,ve(n,r),{pos:e.reduce((a,c)=>c.hasResult()?Math.min(a,c.from):a,1e8),create:vt,above:s.aboveCursor},o?o.timestamp:Date.now(),r,!1)}map(e){return new D(this.options,this.attrs,Object.assign(Object.assign({},this.tooltip),{pos:e.mapPos(this.tooltip.pos)}),this.timestamp,this.selected,this.disabled)}}class z{constructor(e,t,n){this.active=e,this.id=t,this.open=n}static start(){return new z(xt,"cm-ac-"+Math.floor(Math.random()*2e6).toString(36),null)}update(e){let{state:t}=e,n=t.facet(y),s=(n.override||t.languageDataAt("autocomplete",T(t)).map(rt)).map(r=>(this.active.find(c=>c.source==r)||new w(r,this.active.some(c=>c.state!=0)?1:0)).update(e,n));s.length==this.active.length&&s.every((r,a)=>r==this.active[a])&&(s=this.active);let l=this.open;l&&e.docChanged&&(l=l.map(e.changes)),e.selection||s.some(r=>r.hasResult()&&e.changes.touchesRange(r.from,r.to))||!gt(s,this.active)?l=D.build(s,t,this.id,l,n):l&&l.disabled&&!s.some(r=>r.state==1)&&(l=null),!l&&s.every(r=>r.state!=1)&&s.some(r=>r.hasResult())&&(s=s.map(r=>r.hasResult()?new w(r.source,0):r));for(let r of e.effects)r.is(te)&&(l=l&&l.setSelected(r.value,this.id));return s==this.active&&l==this.open?this:new z(s,this.id,l)}get tooltip(){return this.open?this.open.tooltip:null}get attrs(){return this.open?this.open.attrs:this.active.length?yt:bt}}function gt(i,e){if(i==e)return!0;for(let t=0,n=0;;){for(;t<i.length&&!i[t].hasResult;)t++;for(;n<e.length&&!e[n].hasResult;)n++;let o=t==i.length,s=n==e.length;if(o||s)return o==s;if(i[t++].result!=e[n++].result)return!1}}const yt={"aria-autocomplete":"list"},bt={};function ve(i,e){let t={"aria-autocomplete":"list","aria-haspopup":"listbox","aria-controls":i};return e>-1&&(t["aria-activedescendant"]=i+"-"+e),t}const xt=[];function Ce(i,e){if(i.isUserEvent("input.complete")){let n=i.annotation(V);if(n&&e.activateOnCompletion(n))return 12}let t=i.isUserEvent("input.type");return t&&e.activateOnTyping?5:t?1:i.isUserEvent("delete.backward")?2:i.selection?8:i.docChanged?16:0}class w{constructor(e,t,n=-1){this.source=e,this.state=t,this.explicitPos=n}hasResult(){return!1}update(e,t){let n=Ce(e,t),o=this;(n&8||n&16&&this.touches(e))&&(o=new w(o.source,0)),n&4&&o.state==0&&(o=new w(this.source,1)),o=o.updateFor(e,n);for(let s of e.effects)if(s.is(H))o=new w(o.source,1,s.value?T(e.state):-1);else if(s.is(F))o=new w(o.source,0);else if(s.is(Se))for(let l of s.value)l.source==o.source&&(o=l);return o}updateFor(e,t){return this.map(e.changes)}map(e){return e.empty||this.explicitPos<0?this:new w(this.source,this.state,e.mapPos(this.explicitPos))}touches(e){return e.changes.touchesRange(T(e.state))}}class M extends w{constructor(e,t,n,o,s){super(e,2,t),this.result=n,this.from=o,this.to=s}hasResult(){return!0}updateFor(e,t){var n;if(!(t&3))return this.map(e.changes);let o=this.result;o.map&&!e.changes.empty&&(o=o.map(o,e.changes));let s=e.changes.mapPos(this.from),l=e.changes.mapPos(this.to,1),r=T(e.state);if((this.explicitPos<0?r<=s:r<this.from)||r>l||!o||t&2&&T(e.startState)==this.from)return new w(this.source,t&4?1:0);let a=this.explicitPos<0?-1:e.changes.mapPos(this.explicitPos);return wt(o.validFor,e.state,s,l)?new M(this.source,a,o,s,l):o.update&&(o=o.update(o,s,l,new _(e.state,r,a>=0)))?new M(this.source,a,o,o.from,(n=o.to)!==null&&n!==void 0?n:T(e.state)):new w(this.source,1,a)}map(e){return e.empty?this:(this.result.map?this.result.map(this.result,e):this.result)?new M(this.source,this.explicitPos<0?-1:e.mapPos(this.explicitPos),this.result,e.mapPos(this.from),e.mapPos(this.to,1)):new w(this.source,0)}touches(e){return e.changes.touchesRange(this.from,this.to)}}function wt(i,e,t,n){if(!i)return!1;let o=e.sliceDoc(t,n);return typeof i=="function"?i(o,t,n,e):ge(i,!0).test(o)}const Se=I.define({map(i,e){return i.map(t=>t.map(e))}}),te=I.define(),m=X.define({create(){return z.start()},update(i,e){return i.update(e)},provide:i=>[et.from(i,e=>e.tooltip),R.contentAttributes.from(i,e=>e.attrs)]});function ie(i,e){const t=e.completion.apply||e.completion.label;let n=i.state.field(m).active.find(o=>o.source==e.source);return n instanceof M?(typeof t=="string"?i.dispatch(Object.assign(Object.assign({},ye(i.state,t,n.from,n.to)),{annotations:V.of(e.completion)})):t(i,e.completion,n.from,n.to),!0):!1}const vt=ut(m,ie);function j(i,e="option"){return t=>{let n=t.state.field(m,!1);if(!n||!n.open||n.open.disabled||Date.now()-n.open.timestamp<t.state.facet(y).interactionDelay)return!1;let o=1,s;e=="page"&&(s=he(t,n.open.tooltip))&&(o=Math.max(2,Math.floor(s.dom.offsetHeight/s.dom.querySelector("li").offsetHeight)-1));let{length:l}=n.open.options,r=n.open.selected>-1?n.open.selected+o*(i?1:-1):i?0:l-1;return r<0?r=e=="page"?0:l-1:r>=l&&(r=e=="page"?l-1:0),t.dispatch({effects:te.of(r)}),!0}}const Ie=i=>{let e=i.state.field(m,!1);return i.state.readOnly||!e||!e.open||e.open.selected<0||e.open.disabled||Date.now()-e.open.timestamp<i.state.facet(y).interactionDelay?!1:ie(i,e.open.options[e.open.selected])},Oe=i=>i.state.field(m,!1)?(i.dispatch({effects:H.of(!0)}),!0):!1,Te=i=>{let e=i.state.field(m,!1);return!e||!e.active.some(t=>t.state!=0)?!1:(i.dispatch({effects:F.of(null)}),!0)};class Ct{constructor(e,t){this.active=e,this.context=t,this.time=Date.now(),this.updates=[],this.done=void 0}}const St=50,It=1e3,Ot=tt.fromClass(class{constructor(i){this.view=i,this.debounceUpdate=-1,this.running=[],this.debounceAccept=-1,this.pendingStart=!1,this.composing=0;for(let e of i.state.field(m).active)e.state==1&&this.startQuery(e)}update(i){let e=i.state.field(m),t=i.state.facet(y);if(!i.selectionSet&&!i.docChanged&&i.startState.field(m)==e)return;let n=i.transactions.some(s=>{let l=Ce(s,t);return l&8||(s.selection||s.docChanged)&&!(l&3)});for(let s=0;s<this.running.length;s++){let l=this.running[s];if(n||l.context.abortOnDocChange&&i.docChanged||l.updates.length+i.transactions.length>St&&Date.now()-l.time>It){for(let r of l.context.abortListeners)try{r()}catch(a){Z(this.view.state,a)}l.context.abortListeners=null,this.running.splice(s--,1)}else l.updates.push(...i.transactions)}this.debounceUpdate>-1&&clearTimeout(this.debounceUpdate),i.transactions.some(s=>s.effects.some(l=>l.is(H)))&&(this.pendingStart=!0);let o=this.pendingStart?50:t.activateOnTypingDelay;if(this.debounceUpdate=e.active.some(s=>s.state==1&&!this.running.some(l=>l.active.source==s.source))?setTimeout(()=>this.startUpdate(),o):-1,this.composing!=0)for(let s of i.transactions)s.isUserEvent("input.type")?this.composing=2:this.composing==2&&s.selection&&(this.composing=3)}startUpdate(){this.debounceUpdate=-1,this.pendingStart=!1;let{state:i}=this.view,e=i.field(m);for(let t of e.active)t.state==1&&!this.running.some(n=>n.active.source==t.source)&&this.startQuery(t)}startQuery(i){let{state:e}=this.view,t=T(e),n=new _(e,t,i.explicitPos==t,this.view),o=new Ct(i,n);this.running.push(o),Promise.resolve(i.source(n)).then(s=>{o.context.aborted||(o.done=s||null,this.scheduleAccept())},s=>{this.view.dispatch({effects:F.of(null)}),Z(this.view.state,s)})}scheduleAccept(){this.running.every(i=>i.done!==void 0)?this.accept():this.debounceAccept<0&&(this.debounceAccept=setTimeout(()=>this.accept(),this.view.state.facet(y).updateSyncTime))}accept(){var i;this.debounceAccept>-1&&clearTimeout(this.debounceAccept),this.debounceAccept=-1;let e=[],t=this.view.state.facet(y);for(let n=0;n<this.running.length;n++){let o=this.running[n];if(o.done===void 0)continue;if(this.running.splice(n--,1),o.done){let l=new M(o.active.source,o.active.explicitPos,o.done,o.done.from,(i=o.done.to)!==null&&i!==void 0?i:T(o.updates.length?o.updates[0].startState:this.view.state));for(let r of o.updates)l=l.update(r,t);if(l.hasResult()){e.push(l);continue}}let s=this.view.state.field(m).active.find(l=>l.source==o.active.source);if(s&&s.state==1)if(o.done==null){let l=new w(o.active.source,0);for(let r of o.updates)l=l.update(r,t);l.state!=1&&e.push(l)}else this.startQuery(s)}e.length&&this.view.dispatch({effects:Se.of(e)})}},{eventHandlers:{blur(i){let e=this.view.state.field(m,!1);if(e&&e.tooltip&&this.view.state.facet(y).closeOnBlur){let t=e.open&&he(this.view,e.open.tooltip);(!t||!t.dom.contains(i.relatedTarget))&&setTimeout(()=>this.view.dispatch({effects:F.of(null)}),10)}},compositionstart(){this.composing=1},compositionend(){this.composing==3&&setTimeout(()=>this.view.dispatch({effects:H.of(!1)}),20),this.composing=0}}}),Tt=typeof navigator=="object"&&/Win/.test(navigator.platform),Et=Y.highest(R.domEventHandlers({keydown(i,e){let t=e.state.field(m,!1);if(!t||!t.open||t.open.disabled||t.open.selected<0||i.key.length>1||i.ctrlKey&&!(Tt&&i.altKey)||i.metaKey)return!1;let n=t.open.options[t.open.selected],o=t.active.find(l=>l.source==n.source),s=n.completion.commitCharacters||o.result.commitCharacters;return s&&s.indexOf(i.key)>-1&&ie(e,n),!1}})),Ee=R.baseTheme({".cm-tooltip.cm-tooltip-autocomplete":{"& > ul":{fontFamily:"monospace",whiteSpace:"nowrap",overflow:"hidden auto",maxWidth_fallback:"700px",maxWidth:"min(700px, 95vw)",minWidth:"250px",maxHeight:"10em",height:"100%",listStyle:"none",margin:0,padding:0,"& > li, & > completion-section":{padding:"1px 3px",lineHeight:1.2},"& > li":{overflowX:"hidden",textOverflow:"ellipsis",cursor:"pointer"},"& > completion-section":{display:"list-item",borderBottom:"1px solid silver",paddingLeft:"0.5em",opacity:.7}}},"&light .cm-tooltip-autocomplete ul li[aria-selected]":{background:"#17c",color:"white"},"&light .cm-tooltip-autocomplete-disabled ul li[aria-selected]":{background:"#777"},"&dark .cm-tooltip-autocomplete ul li[aria-selected]":{background:"#347",color:"white"},"&dark .cm-tooltip-autocomplete-disabled ul li[aria-selected]":{background:"#444"},".cm-completionListIncompleteTop:before, .cm-completionListIncompleteBottom:after":{content:'"\xB7\xB7\xB7"',opacity:.5,display:"block",textAlign:"center"},".cm-tooltip.cm-completionInfo":{position:"absolute",padding:"3px 9px",width:"max-content",maxWidth:"400px",boxSizing:"border-box",whiteSpace:"pre-line"},".cm-completionInfo.cm-completionInfo-left":{right:"100%"},".cm-completionInfo.cm-completionInfo-right":{left:"100%"},".cm-completionInfo.cm-completionInfo-left-narrow":{right:"30px"},".cm-completionInfo.cm-completionInfo-right-narrow":{left:"30px"},"&light .cm-snippetField":{backgroundColor:"#00000022"},"&dark .cm-snippetField":{backgroundColor:"#ffffff22"},".cm-snippetFieldPosition":{verticalAlign:"text-top",width:0,height:"1.15em",display:"inline-block",margin:"0 -0.7px -.7em",borderLeft:"1.4px dotted #888"},".cm-completionMatchedText":{textDecoration:"underline"},".cm-completionDetail":{marginLeft:"0.5em",fontStyle:"italic"},".cm-completionIcon":{fontSize:"90%",width:".8em",display:"inline-block",textAlign:"center",paddingRight:".6em",opacity:"0.6",boxSizing:"content-box"},".cm-completionIcon-function, .cm-completionIcon-method":{"&:after":{content:"'\u0192'"}},".cm-completionIcon-class":{"&:after":{content:"'\u25CB'"}},".cm-completionIcon-interface":{"&:after":{content:"'\u25CC'"}},".cm-completionIcon-variable":{"&:after":{content:"'\u{1D465}'"}},".cm-completionIcon-constant":{"&:after":{content:"'\u{1D436}'"}},".cm-completionIcon-type":{"&:after":{content:"'\u{1D461}'"}},".cm-completionIcon-enum":{"&:after":{content:"'\u222A'"}},".cm-completionIcon-property":{"&:after":{content:"'\u25A1'"}},".cm-completionIcon-keyword":{"&:after":{content:"'\u{1F511}\uFE0E'"}},".cm-completionIcon-namespace":{"&:after":{content:"'\u25A2'"}},".cm-completionIcon-text":{"&:after":{content:"'abc'",fontSize:"50%",verticalAlign:"middle"}}});class Pt{constructor(e,t,n,o){this.field=e,this.line=t,this.from=n,this.to=o}}class re{constructor(e,t,n){this.field=e,this.from=t,this.to=n}map(e){let t=e.mapPos(this.from,-1,G.TrackDel),n=e.mapPos(this.to,1,G.TrackDel);return t==null||n==null?null:new re(this.field,t,n)}}class ae{constructor(e,t){this.lines=e,this.fieldPositions=t}instantiate(e,t){let n=[],o=[t],s=e.doc.lineAt(t),l=/^\s*/.exec(s.text)[0];for(let a of this.lines){if(n.length){let c=l,f=/^\t*/.exec(a)[0].length;for(let h=0;h<f;h++)c+=e.facet(nt);o.push(t+c.length-f),a=c+a.slice(f)}n.push(a),t+=a.length+1}let r=this.fieldPositions.map(a=>new re(a.field,o[a.line]+a.from,o[a.line]+a.to));return{text:n,ranges:r}}static parse(e){let t=[],n=[],o=[],s;for(let l of e.split(/\r\n?|\n/)){for(;s=/[#$]\{(?:(\d+)(?::([^}]*))?|((?:\\[{}]|[^}])*))\}/.exec(l);){let r=s[1]?+s[1]:null,a=s[2]||s[3]||"",c=-1,f=a.replace(/\\[{}]/g,h=>h[1]);for(let h=0;h<t.length;h++)(r!=null?t[h].seq==r:f&&t[h].name==f)&&(c=h);if(c<0){let h=0;for(;h<t.length&&(r==null||t[h].seq!=null&&t[h].seq<r);)h++;t.splice(h,0,{seq:r,name:f}),c=h;for(let p of o)p.field>=c&&p.field++}o.push(new Pt(c,n.length,s.index,s.index+f.length)),l=l.slice(0,s.index)+a+l.slice(s.index+s[0].length)}l=l.replace(/\\([{}])/g,(r,a,c)=>{for(let f of o)f.line==n.length&&f.from>c&&(f.from--,f.to--);return a}),n.push(l)}return new ae(n,o)}}let At=U.widget({widget:new class extends it{toDOM(){let i=document.createElement("span");return i.className="cm-snippetFieldPosition",i}ignoreEvent(){return!1}}}),Rt=U.mark({class:"cm-snippetField"});class L{constructor(e,t){this.ranges=e,this.active=t,this.deco=U.set(e.map(n=>(n.from==n.to?At:Rt).range(n.from,n.to)))}map(e){let t=[];for(let n of this.ranges){let o=n.map(e);if(!o)return null;t.push(o)}return new L(t,this.active)}selectionInsideField(e){return e.ranges.every(t=>this.ranges.some(n=>n.field==this.active&&n.from<=t.from&&n.to>=t.to))}}const $=I.define({map(i,e){return i&&i.map(e)}}),Dt=I.define(),E=X.define({create(){return null},update(i,e){for(let t of e.effects){if(t.is($))return t.value;if(t.is(Dt)&&i)return new L(i.ranges,t.value)}return i&&e.docChanged&&(i=i.map(e.changes)),i&&e.selection&&!i.selectionInsideField(e.selection)&&(i=null),i},provide:i=>R.decorations.from(i,e=>e?e.deco:U.none)});function ne(i,e){return v.create(i.filter(t=>t.field==e).map(t=>v.range(t.from,t.to)))}function Pe(i){let e=ae.parse(i);return(t,n,o,s)=>{let{text:l,ranges:r}=e.instantiate(t.state,o),a={changes:{from:o,to:s,insert:Ye.of(l)},scrollIntoView:!0,annotations:n?[V.of(n),Ge.userEvent.of("input.complete")]:void 0};if(r.length&&(a.selection=ne(r,0)),r.some(c=>c.field>0)){let c=new L(r,0),f=a.effects=[$.of(c)];t.state.field(E,!1)===void 0&&f.push(I.appendConfig.of([E,Bt,jt,Ee]))}t.dispatch(t.state.update(a))}}function Ae(i){return({state:e,dispatch:t})=>{let n=e.field(E,!1);if(!n||i<0&&n.active==0)return!1;let o=n.active+i,s=i>0&&!n.ranges.some(l=>l.field==o+i);return t(e.update({selection:ne(n.ranges,o),effects:$.of(s?null:new L(n.ranges,o)),scrollIntoView:!0})),!0}}const Re=({state:i,dispatch:e})=>i.field(E,!1)?(e(i.update({effects:$.of(null)})),!0):!1,De=Ae(1),Le=Ae(-1);function Lt(i){let e=i.field(E,!1);return!!(e&&e.ranges.some(t=>t.field==e.active+1))}function Mt(i){let e=i.field(E,!1);return!!(e&&e.active>0)}const kt=[{key:"Tab",run:De,shift:Le},{key:"Escape",run:Re}],oe=fe.define({combine(i){return i.length?i[0]:kt}}),Bt=Y.highest(pe.compute([oe],i=>i.facet(oe)));function Ft(i,e){return Object.assign(Object.assign({},e),{apply:Pe(i)})}const jt=R.domEventHandlers({mousedown(i,e){let t=e.state.field(E,!1),n;if(!t||(n=e.posAtCoords({x:i.clientX,y:i.clientY}))==null)return!1;let o=t.ranges.find(s=>s.from<=n&&s.to>=n);return!o||o.field==t.active?!1:(e.dispatch({selection:ne(t.ranges,o.field),effects:$.of(t.ranges.some(s=>s.field>o.field)?new L(t.ranges,o.field):null),scrollIntoView:!0}),!0)}});function $t(i){let e=i.replace(/[\]\-\\]/g,"\\$&");try{return new RegExp(`[\\p{Alphabetic}\\p{Number}_${e}]+`,"ug")}catch{return new RegExp(`[w${e}]`,"g")}}function Me(i,e){return new RegExp(e(i.source),i.unicode?"u":"")}const ke=Object.create(null);function Wt(i){return ke[i]||(ke[i]=new WeakMap)}function Be(i,e,t,n,o){for(let s=i.iterLines(),l=0;!s.next().done;){let{value:r}=s,a;for(e.lastIndex=0;a=e.exec(r);)if(!n[a[0]]&&l+a.index!=o&&(t.push({type:"text",label:a[0]}),n[a[0]]=!0,t.length>=2e3))return;l+=r.length+1}}function Fe(i,e,t,n,o){let s=i.length>=1e3,l=s&&e.get(i);if(l)return l;let r=[],a=Object.create(null);if(i.children){let c=0;for(let f of i.children){if(f.length>=1e3)for(let h of Fe(f,e,t,n-c,o-c))a[h.label]||(a[h.label]=!0,r.push(h));else Be(f,t,r,a,o-c);c+=f.length+1}}else Be(i,t,r,a,o);return s&&r.length<2e3&&e.set(i,r),r}const Nt=i=>{let e=i.state.languageDataAt("wordChars",i.pos).join(""),t=$t(e),n=i.matchBefore(Me(t,l=>l+"$"));if(!n&&!i.explicit)return null;let o=n?n.from:i.pos,s=Fe(i.state.doc,Wt(e),t,5e4,o);return{from:o,options:s,validFor:Me(t,l=>"^"+l)}},W={brackets:["(","[","{","'",'"'],before:")]}:;>",stringPrefixes:[]},P=I.define({map(i,e){let t=e.mapPos(i,-1,G.TrackAfter);return t??void 0}}),se=new class extends Je{};se.startSide=1,se.endSide=-1;const je=X.define({create(){return Ze.empty},update(i,e){if(i=i.map(e.changes),e.selection){let t=e.state.doc.lineAt(e.selection.main.head);i=i.update({filter:n=>n>=t.from&&n<=t.to})}for(let t of e.effects)t.is(P)&&(i=i.update({add:[se.range(t.value,t.value+1)]}));return i}});function Ut(){return[Ht,je]}const le="()[]{}<>";function $e(i){for(let e=0;e<le.length;e+=2)if(le.charCodeAt(e)==i)return le.charAt(e+1);return ce(i<128?i:i+1)}function We(i,e){return i.languageDataAt("closeBrackets",e)[0]||W}const Vt=typeof navigator=="object"&&/Android\b/.test(navigator.userAgent),Ht=R.inputHandler.of((i,e,t,n)=>{if((Vt?i.composing:i.compositionStarted)||i.state.readOnly)return!1;let o=i.state.selection.main;if(n.length>2||n.length==2&&O(C(n,0))==1||e!=o.from||t!=o.to)return!1;let s=Ue(i.state,n);return s?(i.dispatch(s),!0):!1}),Ne=({state:i,dispatch:e})=>{if(i.readOnly)return!1;let n=We(i,i.selection.main.head).brackets||W.brackets,o=null,s=i.changeByRange(l=>{if(l.empty){let r=zt(i.doc,l.head);for(let a of n)if(a==r&&q(i.doc,l.head)==$e(C(a,0)))return{changes:{from:l.head-a.length,to:l.head+a.length},range:v.cursor(l.head-a.length)}}return{range:o=l}});return o||e(i.update(s,{scrollIntoView:!0,userEvent:"delete.backward"})),!o},qt=[{key:"Backspace",run:Ne}];function Ue(i,e){let t=We(i,i.selection.main.head),n=t.brackets||W.brackets;for(let o of n){let s=$e(C(o,0));if(e==o)return s==o?Xt(i,o,n.indexOf(o+o+o)>-1,t):Kt(i,o,s,t.before||W.before);if(e==s&&Ve(i,i.selection.main.from))return Qt(i,o,s)}return null}function Ve(i,e){let t=!1;return i.field(je).between(0,i.doc.length,n=>{n==e&&(t=!0)}),t}function q(i,e){let t=i.sliceString(e,e+2);return t.slice(0,O(C(t,0)))}function zt(i,e){let t=i.sliceString(e-2,e);return O(C(t,0))==t.length?t:t.slice(1)}function Kt(i,e,t,n){let o=null,s=i.changeByRange(l=>{if(!l.empty)return{changes:[{insert:e,from:l.from},{insert:t,from:l.to}],effects:P.of(l.to+e.length),range:v.range(l.anchor+e.length,l.head+e.length)};let r=q(i.doc,l.head);return!r||/\s/.test(r)||n.indexOf(r)>-1?{changes:{insert:e+t,from:l.head},effects:P.of(l.head+e.length),range:v.cursor(l.head+e.length)}:{range:o=l}});return o?null:i.update(s,{scrollIntoView:!0,userEvent:"input.type"})}function Qt(i,e,t){let n=null,o=i.changeByRange(s=>s.empty&&q(i.doc,s.head)==t?{changes:{from:s.head,to:s.head+t.length,insert:t},range:v.cursor(s.head+t.length)}:n={range:s});return n?null:i.update(o,{scrollIntoView:!0,userEvent:"input.type"})}function Xt(i,e,t,n){let o=n.stringPrefixes||W.stringPrefixes,s=null,l=i.changeByRange(r=>{if(!r.empty)return{changes:[{insert:e,from:r.from},{insert:e,from:r.to}],effects:P.of(r.to+e.length),range:v.range(r.anchor+e.length,r.head+e.length)};let a=r.head,c=q(i.doc,a),f;if(c==e){if(He(i,a))return{changes:{insert:e+e,from:a},effects:P.of(a+e.length),range:v.cursor(a+e.length)};if(Ve(i,a)){let p=t&&i.sliceDoc(a,a+e.length*3)==e+e+e?e+e+e:e;return{changes:{from:a,to:a+p.length,insert:p},range:v.cursor(a+p.length)}}}else{if(t&&i.sliceDoc(a-2*e.length,a)==e+e&&(f=qe(i,a-2*e.length,o))>-1&&He(i,f))return{changes:{insert:e+e+e+e,from:a},effects:P.of(a+e.length),range:v.cursor(a+e.length)};if(i.charCategorizer(a)(c)!=J.Word&&qe(i,a,o)>-1&&!Yt(i,a,e,o))return{changes:{insert:e+e,from:a},effects:P.of(a+e.length),range:v.cursor(a+e.length)}}return{range:s=r}});return s?null:i.update(l,{scrollIntoView:!0,userEvent:"input.type"})}function He(i,e){let t=B(i).resolveInner(e+1);return t.parent&&t.from==e}function Yt(i,e,t,n){let o=B(i).resolveInner(e,-1),s=n.reduce((l,r)=>Math.max(l,r.length),0);for(let l=0;l<5;l++){let r=i.sliceDoc(o.from,Math.min(o.to,o.from+t.length+s)),a=r.indexOf(t);if(!a||a>-1&&n.indexOf(r.slice(0,a))>-1){let f=o.firstChild;for(;f&&f.from==o.from&&f.to-f.from>t.length+a;){if(i.sliceDoc(f.to-t.length,f.to)==t)return!1;f=f.firstChild}return!0}let c=o.to==e&&o.parent;if(!c)break;o=c}return!1}function qe(i,e,t){let n=i.charCategorizer(e);if(n(i.sliceDoc(e-1,e))!=J.Word)return e;for(let o of t){let s=e-o.length;if(i.sliceDoc(s,e)==o&&n(i.sliceDoc(s-1,s))!=J.Word)return s}return-1}function Gt(i={}){return[Et,m,y.of(i),Ot,Jt,Ee]}const ze=[{key:"Ctrl-Space",run:Oe},{key:"Escape",run:Te},{key:"ArrowDown",run:j(!0)},{key:"ArrowUp",run:j(!1)},{key:"PageDown",run:j(!0,"page")},{key:"PageUp",run:j(!1,"page")},{key:"Enter",run:Ie}],Jt=Y.highest(pe.computeN([y],i=>i.facet(y).defaultKeymap?[ze]:[]));function Zt(i){let e=i.field(m,!1);return e&&e.active.some(t=>t.state==1)?"pending":e&&e.active.some(t=>t.state!=0)?"active":null}const Ke=new WeakMap;function _t(i){var e;let t=(e=i.field(m,!1))===null||e===void 0?void 0:e.open;if(!t||t.disabled)return[];let n=Ke.get(t.options);return n||Ke.set(t.options,n=t.options.map(o=>o.completion)),n}function ei(i){var e;let t=(e=i.field(m,!1))===null||e===void 0?void 0:e.open;return t&&!t.disabled&&t.selected>=0?t.options[t.selected].completion:null}function ti(i){var e;let t=(e=i.field(m,!1))===null||e===void 0?void 0:e.open;return t&&!t.disabled&&t.selected>=0?t.selected:null}function ii(i){return te.of(i)}export{_ as CompletionContext,Ie as acceptCompletion,Gt as autocompletion,Re as clearSnippet,Ut as closeBrackets,qt as closeBracketsKeymap,Te as closeCompletion,Nt as completeAnyWord,de as completeFromList,ze as completionKeymap,Zt as completionStatus,_t as currentCompletions,Ne as deleteBracketPair,Lt as hasNextSnippetField,Mt as hasPrevSnippetField,st as ifIn,lt as ifNotIn,Ue as insertBracket,ye as insertCompletionText,j as moveCompletionSelection,De as nextSnippetField,V as pickedCompletion,Le as prevSnippetField,ei as selectedCompletion,ti as selectedCompletionIndex,ii as setSelectedCompletion,Pe as snippet,Ft as snippetCompletion,oe as snippetKeymap,Oe as startCompletion};
