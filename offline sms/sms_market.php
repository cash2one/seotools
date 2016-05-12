<?php
define("lyURI","http://120.26.66.24/msg/HttpBatchSendSM");
define("lyUSER","zzgl_hy");
define("lyPASD","zzgl_hy123");
define("lySIG","【郑锅股份】");
if(isset($_GET['recv'])){
    require_once("./sender.php");
    $recv = urldecode($_GET['recv']);
    $content = urldecode($_GET['content']);
    $callback = $_GET['callback'];
    header("Content-Type:text/javascript");
    if(!$recv){
        echo $callback."('发送失败，发件人不存在!')";
    }
    if(!$content){
        echo $callback."('发送失败，内容不存在!')";
    }
    $lyAPI= new LYAPI(lyURI,lyUSER,lyPASD);
    $sms = lySIG.$content;
    $lyAPI->add_content($sms);
    $result = $lyAPI->sendone('',$recv);

    if($result['error_code'] == 0){
        echo $callback."('".$recv." 发送成功!')";
    }else{
        echo $callback."('".$recv." 发送发送失败 ".$result['reason']."')";
    }
    exit();
}else{
    if(!isset($_SERVER['PHP_AUTH_USER'])){
        header('WWW-Authenticate: Basic realm="login:"');  
        header('HTTP/1.0 401 Unauthorized');  
        echo '需要登陆才能使用!';  
        exit(); 
    }else{
        $u = $_SERVER['PHP_AUTH_USER'];  
        $p = $_SERVER['PHP_AUTH_PW']; 
        if($u!=='admin' and $p !=='supersms'){
            exit("用户名或密码错误，登陆失败!");
        }
    }
}

?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title>郑锅电商渠道短信业务系统</title>
<meta name="author" content="Hito,https://www.hitoy.org/">
<style>
* {margin:0;padding:0}
body {font-size:12px;font-family:Verdana}
.win {width:980px;margin:0 auto;}
.left {float:left;width:300px;margin-top:30px;border:1px solid #eee;border-radius:5px;overflow:hidden}
strong {height:50px;line-height:50px;background:#ccc;display:block;border-top-left-radius:5px;border-top-right-radius:5px;text-indent:20px;font-size:20px}
.list {width:100%;height:700px;overflow-y:scroll;overflow-x:hidden;font-size:14px;background:#ccc}
.list span {display:block;width:100%;height:30px;line-height:30px;margin-bottom:1px;text-indent:12px;cursor:text;background:#fff}
.list span.selected {background:#CBCE14;}
.list span.selected * {color:red}
tel {color:rgb(100,100,255)}
name {color:#ccc;float:right;margin-right:5px;width:80px;height:30px;display:inline-block}
.func {width:100%;height:30px;line-height:30px;text-indent:20px}
.func a {font-size:13px;color:#000}
.main {float:right;width:630px;height:772px;margin-top:30px;border:1px solid #eee;border-radius:5px;padding:10px}
h1 {font-size:30px;margin-bottom:30px}
textarea {width:580px;height:120px;margin:0 25px;resize:none;outline:none;border-radius:5px;box-shadow:5px 5px 10px #ccc}
.main input {width:150px;height:30px;line-height:30px;cursor:pointer;margin-left:30px;margin-top:20px;margin-bottom:20px}
h2 {font-size:16px;margin-top:20px;margin-left:20px}
.status {width:580px;height:300px;overflow:auto;margin:10px 25px;white-space:pre-wrap;margin-bottom:30px}
#clearlog {float:right;margin-right:80px;width:100px;height:20px;}
</style>
<script>
if(!window.localStorage) {alert('不支持此浏览器，请使用高级浏览器\r\n推荐Chrome!')};
//init
///1,database
var db;
var key = 'smslog';
document.addEventListener("DOMContentLoaded",function(){
    db = openDatabase("contactDB","1.0","contact DB",1024*1024*200);
    db.transaction(function(context){
        //context.executeSql("drop table contacts");
        context.executeSql("Create table if not exists contacts(id INTEGER PRIMARY KEY AUTOINCREMENT,tel varchar(15) unique, name varch(20))");
    });
    //2, contacts
    db.transaction(function(context){
        context.executeSql("select * from contacts",[],function(context,result){
            var clist = document.getElementsByClassName("list")[0];
            var len = result.rows.length;
            for(var i = 0 ; i < len ; i++){
                var span = document.createElement("span");
                span.setAttribute('data-id',result.rows.item(i).id);
                span.innerHTML='<tel>'+result.rows.item(i).tel+'</tel><name>'+(result.rows.item(i).rowid||'')+'</name>';
                clist.appendChild(span);
            }
        });
    });
    //3, log
    var data = localStorage.getItem(key);
    document.getElementsByClassName("status")[0].innerHTML= data;
    //4, sendsms
    document.getElementById("sendsms").addEventListener("click",function(){
        var text = document.getElementsByTagName("textarea")[0].value.trim();
        var recv=new Array();
        var span = document.getElementsByClassName("selected");
        for(var i = 0 ; i < span.length;i++){
            recv.push(span[i].childNodes[0].innerHTML);  
        }

        if(text==''){alert("信息内容为空!");return false}
            if(text.length > 64){
                var conf = confirm("信息长度超过限制，是否要发送?");
                if(conf===false) return false;
            }
        if(recv.length==0){
            alert("收件人为空，请选择收件人!");
            return false;
        }
        sendsms(recv,text,1,'write_log',function(){
            write_log("信息全部发送完毕!");
        });
    });
    //5, select recv
    document.getElementsByClassName("list")[0].addEventListener("click",function(e){
        if(e.target.tagName == 'SPAN'){
            e.target.className="selected";
        }else if(e.target.tagName == 'TEL'){
            e.target.parentNode.className="selected";
        }else if(e.target.tagName =='NAME'){
            e.target.setAttribute("contenteditable",true);
        }
    },false);
});

function write_log(log){
    var now = new Date();
    var y = now.getFullYear();
    var m = now.getMonth()+1;
    var d = now.getDate();
    var h = now.getHours();
    var mu = now.getMinutes();
    var s = now.getSeconds();
    var time = y+"-"+m+"-"+d+" "+h+":"+mu+":"+s;
    if(document.readyState == 'complete'){
        var statuswin = document.getElementsByClassName("status")[0];
        statuswin.appendChild(document.createTextNode(time+"  "+log+"\r\n"));
        statuswin.scrollTop+=20;
    }else{
        alert("系统还未加载，请稍后再试!");
    }
    var data = localStorage.getItem(key)||"";
    localStorage.setItem(key,data+time+"  "+log+"\r\n");
}

function clear_log(){
    localStorage.removeItem(key);
    var statuswin = document.getElementsByClassName("status")[0];
    statuswin.innerHTML="";
}

function importtel(e){
    var obj = e.files;
    var len = obj.length;
    for(var i = 0 ; i < len ; i++){
        var file = obj[i];
        if(file.type != 'text/plain'){
            alert("文件"+file.name+"不是文本文件!");
            continue;
        }

        var reader = new FileReader();
        reader.readAsText(file);
        reader.onload=function(){
            save_contactors(reader.result);
        }
    }
}

function save_contactors(data){
    var list = data.split("\r\n");

    for(var i = 0 ;i < list.length; i ++){
        var contact = list[i].trim();
        if(contact.length == 0) continue;
        if(contact.match(/\D/)) {console.log(contact+"不属于手机格式，不予录入!");continue;}
        (function(contact){db.transaction(function(context){
            context.executeSql('Insert into contacts (tel) values ("'+contact+'")');
        });
        })(contact);
    }
    var clist = document.getElementsByClassName("list")[0];
    clist.innerHTML="";
    db.transaction(function(context){
        context.executeSql("select * from contacts",[],function(context,result){
            var len = result.rows.length;
            for(var i = 0 ; i < len ; i++){
                var span = document.createElement("span");
                span.setAttribute('data-id',result.rows.item(i).id);
                span.innerHTML='<tel>'+result.rows.item(i).tel+'</tel><name>'+(result.rows.item(i).rowid||'')+'</name>';
                clist.appendChild(span);
            }
        });
    });
}

function selall(){
    var span = document.getElementsByClassName("list")[0].getElementsByTagName("span");
    for(var i =0 ;i < span.length; i++){
        span[i].className="selected";
    }
}

function concanl(){
    var span = document.getElementsByClassName("list")[0].getElementsByTagName("span");
    for(var i =0 ;i < span.length; i++){
        span[i].className="";
    }
}

function getselected(){
    var span = document.getElementsByClassName("list")[0].getElementsByTagName("span");
    var selectedarr=new Array();
    for(var i =0 ;i < span.length; i++){
        if(span[i].className=="selected") selectedarr.push(span[i].getAttribute('data-id'));
    }
    return selectedarr;
}

function getElementByDataId(id){
    var span = document.getElementsByClassName("list")[0].getElementsByTagName("span");
    for(var i =0 ;i < span.length; i++){
        if(span[i].getAttribute('data-id') == id) return span[i];
    }
}

function remove(){
    var selectedarr = getselected();
    for(var i =0; i < selectedarr.length; i++){
        (function(){
            var id = selectedarr[i];
            db.transaction(function(context){
                context.executeSql('delete from contacts where id = '+id);
            });
            document.getElementsByClassName("list")[0].removeChild(getElementByDataId(id));
        })(i);
    }
}


function sendsms(recptarr,content,sleep,callback,fullcallback){
    var len = recptarr.length,i=0;
    var timer = setInterval(function(){
        if(i==len){
            clearInterval(timer);   
            fullcallback();
            return;
        }
        var  recv = recptarr[i];        
        var script = document.createElement("script");
        script.src="<?php echo $_SERVER['REQUEST_URI']?>?content="+content+"&recv="+recv+"&callback="+callback;
        document.body.appendChild(script);
        script.onload=function(){
            document.body.removeChild(script);
        }
        script.onerror=function(){
            document.body.removeChild(script);
        }
        i++;    
    },sleep*1000);
}
</script>
</head>
<body>
<div class="win">
    <div class="left">
        <strong>联系人列表</strong>
        <div class="list"></div>
        <div class="func">
            <a href="javascript:selall()">全选</a>
            <a href="javascript:concanl()">取消</a>
            <a href="javascript:remove()">删除</a>
        </div>
    </div>
    <div class="main">
        <h1>郑锅股份短信发送系统V0.01</h1>
        <textarea placeholder="短信内容"></textarea>
        <input type="button" value="开始发送" id="sendsms">
        <hr>
        <h2>发送状态</h2>
        <div class="status"></div>
        <hr>
        <h2>设置</h2>
        <label>导入电话:</label><input type="file" multiple="multiple" onchange="importtel(this)">
        <input type="button" value="清空发送状态" onclick="clear_log()">
    </div>
</div>
</body>
</html>
