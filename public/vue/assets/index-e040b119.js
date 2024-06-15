function B(e){if(typeof e!="number")return"0B";const i=["B","KB","MB","GB","TB","PB"];let t=0;for(;e>1024;)e=e/1024,t++;return e=e.toFixed(2),`${e}${i[t]}`}export{B as g};
