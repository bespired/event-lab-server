import{_ as p}from"./index-BI7Nc4ND.js";import{o,b as c,j as d,h as a,t as u,F as _}from"./vendor-Cz5a7hiG.js";const h={props:{label:{type:String,default:null},step:{type:String,default:null}},mounted(){let n=this.$refs.this_slot.textContent,t="",s=!0,l=!0,e=!0;n.split("").forEach(r=>{switch(r){case'"':t+=e?'"<span class="db-quot">':'</span>"',e=!e;break;case"`":t+=l?'<span class="db-table">':"</span>",l=!l;break;case"*":t+=s?'<span class="db-column">':"</span>",s=!s;break;default:t+=r}}),t=t.replaceAll(" --","<br >"),this.html=t},data(){return{rand:`key-${Math.random()}`,html:null}}},m={class:"step"},f={class:"step-label"},b=["innerHTML"];function k(n,t,s,l,e,i){return o(),c(_,null,[(o(),c("span",{style:{display:"none"},ref:"this_slot",key:e.rand},[d(n.$slots,"default")])),a("div",m,[a("div",f,u(s.label),1),a("div",{innerHTML:e.html},null,8,b)])],64)}const x=p(h,[["render",k]]);export{x as _};
