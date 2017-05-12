function dpEncode(str) {
	return str.replace(/&amp;/g, '&');
}

function dpRadio2btngroup() {
	// Turn radios into btn-group
	jQuery('.radio.btn-group label').addClass('btn');

	jQuery('.btn-group label:not(.active)').click(
			function() {
				var label = jQuery(this);
				var input = jQuery('#' + label.attr('for'));

				if (!input.prop('checked')) {
					label.closest('.btn-group').find('label').removeClass(
							'active btn-success btn-danger btn-primary');

					if (input.val() == '') {
						label.addClass('active btn-primary');
					} else if (input.val() == 0) {
						label.addClass('active btn-danger');
					} else {
						label.addClass('active btn-success');
					}

					input.prop('checked', true);
				}
			});

	jQuery(".btn-group input:checked").each(
			function() {
				if (jQuery(this).val() == '') {
					jQuery("label[for=" + jQuery(this).attr('id') + "]")
							.addClass('active btn-primary');
				} else if (jQuery(this).val() == 0) {
					jQuery("label[for=" + jQuery(this).attr('id') + "]")
							.addClass('active btn-danger');
				} else {
					jQuery("label[for=" + jQuery(this).attr('id') + "]")
							.addClass('active btn-success');
				}
			});
}

function endsWith(str, suffix) {
	return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

function pad(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}


!function(){md5=function(n){function r(n,r,t,u,o,c){return r=h(h(r,n),h(u,c)),h(r<<o|r>>>32-o,t)}function t(n,t,u,o,c,e,f){return r(t&u|~t&o,n,t,c,e,f)}function u(n,t,u,o,c,e,f){return r(t&o|u&~o,n,t,c,e,f)}function o(n,t,u,o,c,e,f){return r(t^u^o,n,t,c,e,f)}function c(n,t,u,o,c,e,f){return r(u^(t|~o),n,t,c,e,f)}function e(n,r){var e=n[0],f=n[1],i=n[2],a=n[3];e=t(e,f,i,a,r[0],7,-680876936),a=t(a,e,f,i,r[1],12,-389564586),i=t(i,a,e,f,r[2],17,606105819),f=t(f,i,a,e,r[3],22,-1044525330),e=t(e,f,i,a,r[4],7,-176418897),a=t(a,e,f,i,r[5],12,1200080426),i=t(i,a,e,f,r[6],17,-1473231341),f=t(f,i,a,e,r[7],22,-45705983),e=t(e,f,i,a,r[8],7,1770035416),a=t(a,e,f,i,r[9],12,-1958414417),i=t(i,a,e,f,r[10],17,-42063),f=t(f,i,a,e,r[11],22,-1990404162),e=t(e,f,i,a,r[12],7,1804603682),a=t(a,e,f,i,r[13],12,-40341101),i=t(i,a,e,f,r[14],17,-1502002290),f=t(f,i,a,e,r[15],22,1236535329),e=u(e,f,i,a,r[1],5,-165796510),a=u(a,e,f,i,r[6],9,-1069501632),i=u(i,a,e,f,r[11],14,643717713),f=u(f,i,a,e,r[0],20,-373897302),e=u(e,f,i,a,r[5],5,-701558691),a=u(a,e,f,i,r[10],9,38016083),i=u(i,a,e,f,r[15],14,-660478335),f=u(f,i,a,e,r[4],20,-405537848),e=u(e,f,i,a,r[9],5,568446438),a=u(a,e,f,i,r[14],9,-1019803690),i=u(i,a,e,f,r[3],14,-187363961),f=u(f,i,a,e,r[8],20,1163531501),e=u(e,f,i,a,r[13],5,-1444681467),a=u(a,e,f,i,r[2],9,-51403784),i=u(i,a,e,f,r[7],14,1735328473),f=u(f,i,a,e,r[12],20,-1926607734),e=o(e,f,i,a,r[5],4,-378558),a=o(a,e,f,i,r[8],11,-2022574463),i=o(i,a,e,f,r[11],16,1839030562),f=o(f,i,a,e,r[14],23,-35309556),e=o(e,f,i,a,r[1],4,-1530992060),a=o(a,e,f,i,r[4],11,1272893353),i=o(i,a,e,f,r[7],16,-155497632),f=o(f,i,a,e,r[10],23,-1094730640),e=o(e,f,i,a,r[13],4,681279174),a=o(a,e,f,i,r[0],11,-358537222),i=o(i,a,e,f,r[3],16,-722521979),f=o(f,i,a,e,r[6],23,76029189),e=o(e,f,i,a,r[9],4,-640364487),a=o(a,e,f,i,r[12],11,-421815835),i=o(i,a,e,f,r[15],16,530742520),f=o(f,i,a,e,r[2],23,-995338651),e=c(e,f,i,a,r[0],6,-198630844),a=c(a,e,f,i,r[7],10,1126891415),i=c(i,a,e,f,r[14],15,-1416354905),f=c(f,i,a,e,r[5],21,-57434055),e=c(e,f,i,a,r[12],6,1700485571),a=c(a,e,f,i,r[3],10,-1894986606),i=c(i,a,e,f,r[10],15,-1051523),f=c(f,i,a,e,r[1],21,-2054922799),e=c(e,f,i,a,r[8],6,1873313359),a=c(a,e,f,i,r[15],10,-30611744),i=c(i,a,e,f,r[6],15,-1560198380),f=c(f,i,a,e,r[13],21,1309151649),e=c(e,f,i,a,r[4],6,-145523070),a=c(a,e,f,i,r[11],10,-1120210379),i=c(i,a,e,f,r[2],15,718787259),f=c(f,i,a,e,r[9],21,-343485551),n[0]=h(e,n[0]),n[1]=h(f,n[1]),n[2]=h(i,n[2]),n[3]=h(a,n[3])}function f(n){txt="";var r,t=n.length,u=[1732584193,-271733879,-1732584194,271733878];for(r=64;t>=r;r+=64)e(u,i(n.substring(r-64,r)));n=n.substring(r-64);var o=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],c=n.length;for(r=0;c>r;r++)o[r>>2]|=n.charCodeAt(r)<<(r%4<<3);if(o[r>>2]|=128<<(r%4<<3),r>55)for(e(u,o),r=16;r--;)o[r]=0;return o[14]=8*t,e(u,o),u}function i(n){var r,t=[];for(r=0;64>r;r+=4)t[r>>2]=n.charCodeAt(r)+(n.charCodeAt(r+1)<<8)+(n.charCodeAt(r+2)<<16)+(n.charCodeAt(r+3)<<24);return t}function a(n){for(var r="",t=0;4>t;t++)r+=v[n>>8*t+4&15]+v[n>>8*t&15];return r}function d(n){for(var r=n.length,t=0;r>t;t++)n[t]=a(n[t]);return n.join("")}function h(n,r){return n+r&4294967295}function h(n,r){var t=(65535&n)+(65535&r),u=(n>>16)+(r>>16)+(t>>16);return u<<16|65535&t}var v="0123456789abcdef".split("");return"5d41402abc4b2a76b9719d911017c592"!=d(f("hello")),d(f(n))}}();

// https://github.com/Mikhus/jsurl
!function(t){"use strict";function r(t){var r={path:!0,query:!0,hash:!0};return t?(/^[a-z]+:/.test(t)&&(r.protocol=!0,r.host=!0,/[-a-z0-9]+(\.[-a-z0-9])*:\d+/i.test(t)&&(r.port=!0),/\/\/(.*?)(?::(.*?))?@/.test(t)&&(r.user=!0,r.pass=!0)),r):r}function e(t,e,o){var u,f,l,y=h?"file://"+(process.platform.match(/^win/i)?"/":"")+p("fs").realpathSync("."):document.location.href;e||(e=y),h?u=p("url").parse(e):(u=document.createElement("a"),u.href=e);var d=r(e);l=e.match(/\/\/(.*?)(?::(.*?))?@/)||[];for(f in a)t[f]=d[f]?u[a[f]]||"":"";if(t.protocol=t.protocol.replace(/:$/,""),t.query=t.query.replace(/^\?/,""),t.hash=s(t.hash.replace(/^#/,"")),t.user=s(l[1]||""),t.pass=s(l[2]||""),t.port=c[t.protocol]==t.port||0==t.port?"":t.port,!d.protocol&&/[^\/#?]/.test(e.charAt(0))&&(t.path=e.split("?")[0].split("#")[0]),!d.protocol&&o){var g=new n(y.match(/(.*\/)/)[0]),m=g.path.split("/"),v=t.path.split("/"),q=["protocol","user","pass","host","port"],w=q.length;for(m.pop(),f=0;w>f;f++)t[q[f]]=g[q[f]];for(;".."===v[0];)m.pop(),v.shift();t.path=("/"!==e.charAt(0)?m.join("/"):"")+"/"+v.join("/")}t.path=t.path.replace(/^\/{2,}/,"/"),t.paths(("/"===t.path.charAt(0)?t.path.slice(1):t.path).split("/")),t.query=new i(t.query)}function o(t){return encodeURIComponent(t).replace(/'/g,"%27")}function s(t){return t=t.replace(/\+/g," "),t=t.replace(/%([ef][0-9a-f])%([89ab][0-9a-f])%([89ab][0-9a-f])/gi,function(t,r,e,o){var s=parseInt(r,16)-224,i=parseInt(e,16)-128;if(0===s&&32>i)return t;var n=parseInt(o,16)-128,h=(s<<12)+(i<<6)+n;return h>65535?t:String.fromCharCode(h)}),t=t.replace(/%([cd][0-9a-f])%([89ab][0-9a-f])/gi,function(t,r,e){var o=parseInt(r,16)-192;if(2>o)return t;var s=parseInt(e,16)-128;return String.fromCharCode((o<<6)+s)}),t.replace(/%([0-7][0-9a-f])/gi,function(t,r){return String.fromCharCode(parseInt(r,16))})}function i(t){for(var r,e=/([^=&]+)(=([^&]*))?/g;r=e.exec(t);){var o=decodeURIComponent(r[1].replace(/\+/g," ")),i=r[3]?s(r[3]):"";void 0!==this[o]&&null!==this[o]?(this[o]instanceof Array||(this[o]=[this[o]]),this[o].push(i)):this[o]=i}}function n(t,r){e(this,t,!r)}var h="undefined"==typeof window&&"undefined"!=typeof global&&"function"==typeof require,p=h?t.require:null,a={protocol:"protocol",host:"hostname",port:"port",path:"pathname",query:"search",hash:"hash"},c={ftp:21,gopher:70,http:80,https:443,ws:80,wss:443};i.prototype.toString=function(){var t,r,e="",s=o;for(t in this)if(!(this[t]instanceof Function||null===this[t]))if(this[t]instanceof Array){var i=this[t].length;if(i)for(r=0;i>r;r++)e+=e?"&":"",e+=s(t)+"="+s(this[t][r]);else e+=(e?"&":"")+s(t)+"="}else e+=e?"&":"",e+=s(t)+"="+s(this[t]);return e},n.prototype.clearQuery=function(){for(var t in this.query)this.query[t]instanceof Function||delete this.query[t];return this},n.prototype.queryLength=function(){var t,r=0;for(t in this)this[t]instanceof Function||r++;return r},n.prototype.isEmptyQuery=function(){return 0===this.queryLength()},n.prototype.paths=function(t){var r,e="",i=0;if(t&&t.length&&t+""!==t){for(this.isAbsolute()&&(e="/"),r=t.length;r>i;i++)t[i]=!i&&t[i].match(/^\w:$/)?t[i]:o(t[i]);this.path=e+t.join("/")}for(t=("/"===this.path.charAt(0)?this.path.slice(1):this.path).split("/"),i=0,r=t.length;r>i;i++)t[i]=s(t[i]);return t},n.prototype.encode=o,n.prototype.decode=s,n.prototype.isAbsolute=function(){return this.protocol||"/"===this.path.charAt(0)},n.prototype.toString=function(){return(this.protocol&&this.protocol+"://")+(this.user&&o(this.user)+(this.pass&&":"+o(this.pass))+"@")+(this.host&&this.host)+(this.port&&":"+this.port)+(this.path&&this.path)+(this.query.toString()&&"?"+this.query)+(this.hash&&"#"+o(this.hash))},t[t.exports?"exports":"Url"]=n}("undefined"!=typeof module&&module.exports?module:window);
