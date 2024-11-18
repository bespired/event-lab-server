console.log('Loaded Eventlab Start')

function fpiogi() { var e=Array.prototype.forEach,n=Array.prototype.map;this.each=function(n,r,t){if(null!=n)if(e&&n.forEach===e)n.forEach(r,t);else if(n.length===+n.length){for(var i=0,o=n.length;i<o;i++)if(r.call(t,n[i],i,n)==={})return}else for(var s in n)if(n.hasOwnProperty(s)&&r.call(t,n[s],s,n)==={})return},this.map=function(e,r,t){var i=[];return null==e?i:n&&e.map===n?e.map(r,t):(this.each(e,(function(e,n,o){i[i.length]=r.call(t,e,n,o)})),i)}}fpiogi.prototype={get:function(){keys=[],keys.push(navigator.userAgent),keys.push([screen.height,screen.width,screen.colorDepth].join("x")),keys.push((new Date).getTimezoneOffset()),keys.push(!!window.sessionStorage),keys.push(!!window.localStorage);var e=this.map(navigator.plugins,(function(e){var n=this.map(e,(function(e){return[e.type,e.suffixes].join("~")})).join(",");return[e.name,e.description,n].join("::")}),this).join(";");return keys.push(e),this.hashcode(keys.join("::"))},hashcode:function(e){for(var n=0,r=0,t=e.length;r<t;)n=(n<<5)-n+e.charCodeAt(r++)<<0;return n+2147483647+1}};
function atuiaj() {

    var url = 'https://77.161.128.35:8080/--/tracker/start.php'

    var token = localStorage.getItem('eventlab-token')
    var session = sessionStorage.getItem('eventlab-session')
    var mode = ((session) ? 'session' : (token ? 'next' : 'first'))

    switch (mode) {
        case 'first':
            url += "?user=" + new fpiogi().get();
            break;
        case 'session':
            url += "?session=" + session;
            break;
        case 'next':
            url += "?next=" + session;
            break;
    }

    fetch(url)
        .then((r) => { return r.json() })
        .then((j) => {
            console.log( j )
        })
}

atuiaj();