import{_ as n}from"./StepWrap-B11qd6q9.js";import{_ as l}from"./index-DlGLH8OJ.js";import{o as a,b as i,e as t,h as d,i as r,k as o}from"./vendor-Cz5a7hiG.js";const f={},p={class:"scroll-content"};function u(b,e){const s=n;return a(),i("div",p,[e[6]||(e[6]=t(" Tracker Life Cycle ")),e[7]||(e[7]=d("br",null,null,-1)),r(s,{step:"1",label:"load"},{default:o(()=>e[0]||(e[0]=[t(" include `start.js` ")])),_:1}),r(s,{step:"2",label:"first"},{default:o(()=>e[1]||(e[1]=[t(" is this first visit? -- Or do we have a token stored in sessionStorage? ")])),_:1}),r(s,{step:"3",label:"send"},{default:o(()=>e[2]||(e[2]=[t(" is there an `elrid` in the url? -- add it to the payload -- -- if first then send fingerprint -- - else if sessionStorage send sessionStorage -- - else send localStorage for a session token -- send data to server -- - store eventlab-token in localStorage -- - store eventlab-session in sessionStorage -- I guess both to update the expire date. ")])),_:1}),r(s,{step:"4",label:"layer"},{default:o(()=>e[3]||(e[3]=[t(" Create event layer -- ")])),_:1}),r(s,{step:"5",label:"send"},{default:o(()=>e[4]||(e[4]=[t(" Send basics to server -- User agent, screen size, device type, -- ")])),_:1}),r(s,{step:"6",label:"send"},{default:o(()=>e[5]||(e[5]=[t(" Send events from event layer to server -- ")])),_:1})])}const _=l(f,[["render",u]]);export{_ as default};