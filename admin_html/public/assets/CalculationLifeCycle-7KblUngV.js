import{_ as o}from"./StepWrap-BNtaWYxV.js";import{_ as n}from"./index-BI7Nc4ND.js";import{o as i,b as u,e as t,h as a,i as l,k as r}from"./vendor-Cz5a7hiG.js";const d={},p={class:"scroll-content"};function _(f,e){const s=o;return i(),u("div",p,[e[6]||(e[6]=t(" Calculation Life Cycle ")),e[7]||(e[7]=a("br",null,null,-1)),e[8]||(e[8]=t(" Start calculation every 2 hours or is_changed > 500 or is_new > 500 (?)")),e[9]||(e[9]=a("br",null,null,-1)),l(s,{step:"1",label:"collect"},{default:r(()=>e[0]||(e[0]=[t(" Create records in *results_write* for any `is_new` from tables with *accu_* as prefix. -- Update records in *results_write* for any `is_changed` from tables with *accu_* as prefix. -- ")])),_:1}),l(s,{step:"2",label:"cleanup"},{default:r(()=>e[1]||(e[1]=[t(" Remove old prospects from *results_write* where profile is no contact and to old-- Remove old prospects from *profiles* -- (`DELETE t1 FROM Table1 t1 JOIN Table2 t2 ON t1.ID = t2.ID; ...?`) -- (`DELETE FROM profiles WHERE is_contact=0 AND lastvisitdate ... ;`) ")])),_:1}),l(s,{step:"3",label:"calculate"},{default:r(()=>e[2]||(e[2]=[t(" Calculate timeline in *results_write* for -- - `accu_has_utms` based on attributes -- - `accu_on_journeys` based on journeys -- ")])),_:1}),l(s,{step:"3",label:"calculate"},{default:r(()=>e[3]||(e[3]=[t(" Calculate accumulators in *results_write* for -- - `accu_in_panels` based on panel query builders -- - `accu_in_segments` based on panels and qualifier builders -- ")])),_:1}),l(s,{step:"8",label:"aggrigate"},{default:r(()=>e[4]||(e[4]=[t(" Aggrigate in today, this month and this year. -- ")])),_:1}),l(s,{step:"8",label:"roll-up"},{default:r(()=>e[5]||(e[5]=[t(' Swap *results_read* with *results_write* -- (`RENAME TABLE results_read TO results_write, results_write To results_read;`) -- In *results_write* set `collected` to "0" and `status` to "reset" -- ')])),_:1})])}const w=n(d,[["render",_]]);export{w as default};
