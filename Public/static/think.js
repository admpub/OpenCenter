/* 扩展ThinkPHP对象 */
(function($){
	/**
	 * 获取ThinkPHP基础配置
	 * @type {object}
	 */
	var ThinkPHP = window.Think;

	/* 基础对象检测 */
	ThinkPHP || $.error("ThinkPHP基础配置没有正确加载！");

	/**
	 * 解析URL
	 * @param  {string} url 被解析的URL
	 * @return {object}     解析后的数据
	 */
	ThinkPHP.parse_url = function(url){
		var parse = url.match(/^(?:([a-z]+):\/\/)?([\w-]+(?:\.[\w-]+)+)?(?::(\d+))?([\w-\/]+)?(?:\?((?:\w+=[^#&=\/]*)?(?:&\w+=[^#&=\/]*)*))?(?:#([\w-]+))?$/i);
		parse || $.error("url格式不正确！");
		return {
			"scheme"   : parse[1],
			"host"     : parse[2],
			"port"     : parse[3],
			"path"     : parse[4],
			"query"    : parse[5],
			"fragment" : parse[6]
		};
	};

	ThinkPHP.parse_str = function(str){
		var value = str.split("&"), vars = {}, param;
		for(val in value){
			param = value[val].split("=");
			vars[param[0]] = param[1];
		}
		return vars;
	};

	ThinkPHP.parse_name = function(name, type){
		if(type){
			/* 下划线转驼峰 */
			name.replace(/_([a-z])/g, function($0, $1){
				return $1.toUpperCase();
			});

			/* 首字母大写 */
			name.replace(/[a-z]/, function($0){
				return $0.toUpperCase();
			});
		} else {
			/* 大写字母转小写 */
			name = name.replace(/[A-Z]/g, function($0){
				return "_" + $0.toLowerCase();
			});

			/* 去掉首字符的下划线 */
			if(0 === name.indexOf("_")){
				name = name.substr(1);
			}
		}
		return name;
	};

	//scheme://host:port/path?query#fragment
	ThinkPHP.U = function(url, vars, suffix){
		var info = this.parse_url(url), path = [], param = {}, reg;

		/* 验证info */
		info.path || $.error("url格式错误！");
		url = info.path;

		/* 组装URL */
		if(0 === url.indexOf("/")){ //路由模式
			this.MODEL[0] == 0 && $.error("该URL模式不支持使用路由!(" + url + ")");

			/* 去掉右侧分割符 */
			if("/" == url.substr(-1)){
				url = url.substr(0, url.length -1)
			}
			url = ("/" == this.DEEP) ? url.substr(1) : url.substr(1).replace(/\//g, this.DEEP);
			url = "/" + url;
		} else { //非路由模式
			/* 解析URL */
			path = url.split("/");
			path = [path.pop(), path.pop(), path.pop()].reverse();
			path[1] || $.error("ThinkPHP.U(" + url + ")没有指定控制器");

			if(path[0]){
				param[this.VAR[0]] = this.MODEL[1] ? path[0].toLowerCase() : path[0];
			}

			param[this.VAR[1]] = this.MODEL[1] ? this.parse_name(path[1]) : path[1];
			param[this.VAR[2]] = path[2].toLowerCase();

			url = "?" + $.param(param);
		}

		/* 解析参数 */
		if(typeof vars === "string"){
			vars = this.parse_str(vars);
		} else if(!$.isPlainObject(vars)){
			vars = {};
		}

		/* 解析URL自带的参数 */
		info.query && $.extend(vars, this.parse_str(info.query));

		if(vars){
			url += "&" + $.param(vars);
		}

		if(0 != this.MODEL[0]){
			url = url.replace("?" + (path[0] ? this.VAR[0] : this.VAR[1]) + "=", "/")
				     .replace("&" + this.VAR[1] + "=", this.DEEP)
				     .replace("&" + this.VAR[2] + "=", this.DEEP)
				     .replace(/(\w+=&)|(&?\w+=$)/g, "")
				     .replace(/[&=]/g, this.DEEP);

			/* 添加伪静态后缀 */
			if(false !== suffix){
				suffix = suffix || this.MODEL[2].split("|")[0];
				if(suffix){
					url += "." + suffix;
				}
			}
		}

		url = this.APP + url;
		return url;
	};

	/* 设置表单的值 */
	ThinkPHP.setValue = function(name, value){
		var first = name.substr(0,1), input, i = 0, val;
		if(value === "") return;
		if("#" === first || "." === first){
			input = $(name);
		} else {
			input = $("[name='" + name + "']");
		}

		if(input.eq(0).is(":radio")) { //单选按钮
			input.filter("[value='" + value + "']").each(function(){this.checked = true});
		} else if(input.eq(0).is(":checkbox")) { //复选框
			if(!$.isArray(value)){
				val = new Array();
				val[0] = value;
			} else {
				val = value;
			}
			for(i = 0, len = val.length; i < len; i++){
				input.filter("[value='" + val[i] + "']").each(function(){this.checked = true});
			}
		} else {  //其他表单选项直接设置值
			input.val(value);
		}
	};
    
    /* 搜索功能 author: swh <swh@admpub.com> */
    ThinkPHP.search = function(element){
        var evt=function(event,isForm,vets){
                event.preventDefault();
                var url,query;
                if (isForm) {
                    url = $(element).attr('action');
                    query = $(element).serialize();
                }else{
                    url = $(element).attr('url');
                    if(vets){
                        query = $(vets).serialize();
                    }else{
                        query = $(element).parents('form').serialize();
                    }
                }
                query = query.replace(/(&|^)(\w*?\d*?\-*?_*?)*?=?((?=&)|(?=$))/g,'');
                query = query.replace(/^&/g,'');
                if( url.indexOf('?')>0 ){
                    url += '&' + query;
                }else{
                    url += '?' + query;
                }
                window.location.href = url;
            };
        var tagName=$(element).get(0).tagName;
        if (tagName=='BUTTON'){
            var btnType=$(element).attr('type');
            if(typeof(btnType)=='undefined'||btnType=='submit'){
                tagName='FORM';
            }else{
                tagName='INPUT';
            }
        }
        switch (tagName) {
        case 'FORM':
            $(element).submit(function(event){
                evt(event,true,false);
            });
            $(element).find('input').keyup(function(e){
                if(e.keyCode === 13){
                    $(element).submit();
                    return false;
                }
            });
            break;
              
        case 'INPUT':
        case 'A':
           var tag=$(element).attr('val'),vets='.search-form input';
           if(typeof(tag)!='undefined'){
                if(tag=='1'||tag=='true'){
                    vets='[val="'+tag+'"]';
                }else if(tag!='0'&&tag!='false'){
                    vets=tag;
                }
            }
            $(element).click(function(event){
                evt(event,false,vets);
            });
            $(vets+':input').keyup(function(e){
                if(e.keyCode === 13){
                    $(element).click();
                    return false;
                }
            });
            break;

        default:
            var form=$(element).parents('form');
            form.submit(function(event){
                evt(event,true,false);
            });
            form.find('input').keyup(function(e){
                if(e.keyCode === 13){
                    form.submit();
                    return false;
                }
            });
        }
    };



/**
 * 级联选择(使用前请确保第一个下拉框已有选中项)
 * 使用方法：nestedSelected(["country_id","province_id","city_id"])
 * @author swh <swh@admpub.com>
 */
ThinkPHP.nestedSelected=function (ids, initVal, attrName, timeout){
    if(typeof(ids)=='object'){
        var obj=ids;
        if(typeof(obj.initVal)!='undefined') initVal=obj.initVal;
        if(typeof(obj.attrName)!='undefined') attrName=obj.attrName;
        if(typeof(obj.timeout)!='undefined') timeout=obj.timeout;
        if(typeof(obj.ids)!='undefined') ids=obj.ids;
        obj=null;
    }
    var id=ids[0],id2=ids[1];
    if(initVal==null)initVal='';
    if(attrName==null)attrName='rel';
    if(timeout==null)timeout=5000;
    var attr=$('#'+id2).attr(attrName);
    if(!attr) return false;
    if($('#'+id).val()==initVal) return false;
    if($('#'+id2+' option:last').val()!=initVal) return false;
    $('#'+id).trigger('change');
    var i=0;
    var ptimer=window.setInterval(function(){
        i++;
        if($('#'+id2+' option:last').val()!=initVal || i*200>timeout){
            window.clearInterval(ptimer);
            var sel=$('#'+id2+' option[value="'+attr+'"]');
            if(sel.length<=0)return;
            sel.prop('selected',true);
            ids.shift();
            if(ids.length>1)ThinkPHP.nestedSelected(ids,initVal,attrName,timeout);
        }
    },200);
    return true;
};

/**
 * 级联选择
 * @param  array    idNames     selec标签的id数组，例如：["country_id","province_id","city_id"]
 * @param  string   url         ajax查询网址
 * @param  string   syncToEle   将选中值同步到的隐藏域元素，例如：input[type=hidden][name="cat"]
 * @author swh <swh@admpub.com>
 */
ThinkPHP.nestedSelectedAjax=function (idNames,url,syncToEle){
    if (!url) url=window.location.href;
    ThinkPHP.nestedSelected(idNames);
    var fixLevel=Number($('#'+idNames[0]).attr('level'));
    if(isNaN(fixLevel)){
    	alert('select标签中的level属性值必须是一个数字');
    	return false;
    }
    if (fixLevel==0) {
    	fixLevel=1;
    }else{
    	fixLevel=0;
    }
    $('#'+idNames.join(',#')).change(function(){
        var id=$(this).attr('id'),level=Number($(this).attr('level')),value=$(this).val(),rel=$(this).attr('rel');
        if (isNaN(level)||idNames.length<level+fixLevel) return false;
        $.get(url,{from:'nestedSelect',level:level,id_name:id,value:value},function(r){
            if(typeof(r.data)=='undefined'||r.status==0){
                return false;
            }
            var str='',has=false;
            if (typeof(r.data.length)!='undefined') {
                for (var i = 0; i < r.data.length; i++) {
                    var v=r.data[i];
                    if(typeof(v)!='object')continue;
                    var s='';
                    if(rel==v.key){
                        s=' selected';
                        has=true;
                    }
                    str+='<option value="'+v.key+'"'+s+'>'+v.val+'</option>';
                }
            }else{
                for(var k in r.data){
                    if(typeof(r.data[k])!='string')continue;
                    var s='';
                    if(rel==k){
                        s=' selected';
                        has=true;
                    }
                    str+='<option value="'+k+'"'+s+'>'+r.data[k]+'</option>';
                }
            }
            var nextId=idNames[level+fixLevel];
            $('#'+nextId).html(str);
            if(syncToEle!=null&&has)$(syncToEle).val(rel);
        },'json');
        if(syncToEle!=null)$(syncToEle).val(value);
    });
	$('#'+idNames[0]).trigger('change');
};
})(jQuery);
