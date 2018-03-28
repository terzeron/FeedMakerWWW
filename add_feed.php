<?php
require_once "oauth/oauth_check.php";
require_once "common.php";

//
// initialization
//

$feed_dir = (array_key_exists("feed_dir", $_GET) ? $_GET{"feed_dir"} : "");
$feed_name = (array_key_exists("feed_name", $_GET) ? $_GET{"feed_name"} : "");
list($id2conf_map, $category_list) = scan_dirs($work_dir);
$feed_dir = determine_current_feed_dir($work_dir, $feed_name);
?>

<script type="text/javascript">
 <?print_id2name_map($id2conf_map);?>
</script>

<div class="panel panel-default">
    <div class="panel-heading">
        카테고리: <!--select id="category_list" name="category_dir"-->
        <?foreach ($category_list as $k) {?>
            <!--option name="<?=$k?>" value="<?=$k?>"><?=$k?></option-->
        <?}?>
        <!--/select-->
    </div>
    <div class="panel-body">
        <div id="category_list">
            <?foreach ($category_list as $k) {?>
                <button type="button" class="btn btn-<?=($k[0] != '_' ? 'primary' : 'light')?>" onclick="select_category('<?=$k?>');"><?=$k?></button>
            <?}?>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        샘플 피드: <!--select id="feed_list" name="feed_dir">
                        </select-->
    </div>
    <div class="panel-body">
        <div id="feed_list"></div>
    </div>
</div>

<div class="panel panel-default">
    <div id="xml" class="panel-body">
    </div>
    
    <div class="panel-body">
        <div>
            <span>
                새로운 Feed 이름: <input type='text' id='feed_name' name='feed_name' value=""/>.xml에
                <button id='save' class="btn btn-success" disabled>저장</button>
            </span>
        </div>
        <div>
            <span>
                <button id='lint' class="btn btn-success" disabled>XML lint 실행</button>
                <button id='extract' class="btn btn-success" disabled>추출 실행</button>
                <button id='setacl' class="btn btn-success" disabled>ACL 설정</button>
                <a href='#' class="btn btn-success" role="button" disabled style='cursor:default; pointer-events: none;' id='feedly_link' target="_blank">Feedly 등록</a>
                <button id='disable' class="btn btn-warning">비활성화</button>
                <button id='remove' class="btn btn-danger">삭제</button>
                <button id='reset' class="btn btn-warning">초기화</button>
            </span>
        </div>
        <div id='status'></div>
    </div>
</div>

<script type="text/javascript">
 var ajax_url = "exec.php";
 var selected_category;
 var selected_sample_feed;

 function check_feed_name(feed_name) {
     if (feed_name == undefined || feed_name == "") {
         alert("Feed 이름을 입력하세요." + "feed_name='" + feed_name + "'");
         return -1;
     }
     return 0;
 }

 function get_error_message(str) {
     return "<span style='color:red;'>" + str + "</span>";
 }

 function get_success_message(str) {
     return "<span style='color:green;'>" + str + "</span>";
 }

 select_category = function(category_name) {
     $.post(
         ajax_url,
         { "command": "get_feed_list", "category_name": category_name },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 alert("can't get feed list");
             } else {
                 var id_list = [];
                 for (var i = 0; i < res["message"].length; i++) {
                     var feed_id = res["message"][i];
                     id_list.push(feed_id);
                 }
                 sorted_list = id_list.sort(function(a, b) { return id2name_map[a].localeCompare(id2name_map[b]); });
                 html = "";
                 for (var i = 0; i < sorted_list.length; i++) {
                     var feed_id = sorted_list[i];
                     html += '<button type="button" class="btn btn-' + (feed_id[0] != '_' ? 'primary' : 'light') + '" onclick="select_feed(\'' + category_name + '\', \''+ feed_id + '\');">' + id2name_map[feed_id] + '</button>\n';
                 }
                 $("#feed_list").html(html);
                 selected_category = category_name;
             }
         }
     );
     reset_handler();
 };

 select_feed = function(category_name, sample_feed_name) {
     $.post(
         ajax_url,
         { "command": "get_feed_content", "sample_feed": sample_feed_name, "category_name": category_name },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 alert("can't get feed content");
             } else {
                 html = res["message"];
                 new_html = html;
                 new_html = new_html.replace(/</g, "&lt;")
                                    .replace(/>/g, "&gt;<br/>")
                                    .replace(/\t/g, "&nbsp;&nbsp;&nbsp;&nbsp;")
                                    .replace(/&lt;!\[CDATA\[(.*)\]\]&gt;/g, "&lt;![CDATA[<input class='cdata' type='text' value=\"\$1\" size='100'/>]]&gt;");
                 $("#xml").html(new_html);
                 selected_sample_feed = sample_feed_name;
             }
         }
     );
     reset_handler();
 };


 $(document).ready(function() {
     <?if ($feed_dir != "") {?>
     setTimeout(function() {
         select_category("<?=$feed_dir?>");
         <?if ($feed_name != "") {?>
         setTimeout(function() {
             select_feed("<?=$feed_dir?>", "<?=$feed_name?>");
         }, 200);
         <?}?>
     }, 100);
     <?}?>
 });

 // feed_name input change event handler
 var change_handler = function() {
     if (selected_category && selected_sample_feed) {
         $("#save").removeAttr("disabled");
     } else {
         alert("샘플 피드를 선택해주세요.");
     }
 };
 
 // save button click event handler
 var save_handler = function() {
     $("#save").html("저장 중");
     var feed_name = $("#feed_name").val();
     var category_name = selected_category;
     var sample_feed = $("#feed_list option:selected").val();
     if (check_feed_name(feed_name) < 0) {
         return -1;
     }
     var cdata_arr = read_form_cdata();
     var xml_text = replace_form_with_cdata(cdata_arr);
     $.post(
         ajax_url,
         { "command": "save", "feed_name": feed_name, "category_name": category_name, "sample_feed": sample_feed, "xml_text": xml_text },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 $("#status").html(get_error_message(res["message"]));
             } else {
                 $("#status").html(get_success_message(feed_name + ".xml 파일이 저장되었습니다."));
                 $("#save").html("저장 완료");
                 $("#save").attr("disabled", true);
                 $("#lint").removeAttr("disabled");
                 $("#feedly_link").attr("href", 'https://feedly.com/i/subscription/feed%2Fhttps://terzeron.net/' + feed_name + '.xml');
             }
         }
     );
 };

 // lint button event handler
 var lint_handler = function() {
     $("#lint").html("XML lint 실행 중");
     var feed_name = $("#feed_name").val();
     var category_name = selected_category;
     var sample_feed = $("#feed_list option:selected").val();
     $.post(
         ajax_url,
         { "command": "lint", "feed_name": feed_name, "category_name": category_name, "sample_feed": sample_feed },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 $("#status").html(get_error_message(res["message"]));
             } else {
                 $("#status").html(get_success_message("XML 검사 완료"));
                 $("#lint").html("XML lint 실행 완료");
                 $("#lint").attr("disabled", true);
                 $("#extract").removeAttr("disabled");
             }
         }
     );
 };       

 // extract button event handler
 var extract_handler = function() {
     $("#extract").html("추출 실행 중");
     var feed_name = $("#feed_name").val();
     var category_name = selected_category;
     var sample_feed = $("#feed_list option:selected").val();
     $.post(
         ajax_url,
         { "command": "extract", "feed_name": feed_name, "category_name": category_name, "sample_feed": sample_feed },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 $("#status").html(get_error_message(res["message"]));
                 $("#extract").html("재추출 시도");
             } else {
                 $("#status").html(get_success_message("피드 추출 성공"));
                 $("#extract").html("추출 실행 완료");
                 $("#extract").attr("disabled", true);
                 $("#setacl").removeAttr("disabled");
             }
         }
     );
 };

 // setacl button event handler
 var setAcl_handler = function() {
     $("setacl").html("ACL 설정 중");
     var feed_name = $("#feed_name").val();
     var category_name = selected_category;
     var sample_feed = selected_sample_feed;
     $.post(
         ajax_url,
         { "command": "setacl", "feed_name": feed_name, "category_name": category_name, "sample_feed": sample_feed },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 $("#status").html(get_error_message(res["message"]));
             } else {
                 $("#status").html(get_success_message("ACL 설정 성공"));
                 $("#setacl").html("ACL 설정 완료");
                 $("#setacl").attr("disabled", true);
                 $("#feedly_link").removeAttr("disabled");
                 $("#feedly_link").removeAttr("style");
             }
         }
     );
 };

 // reset button event handler
 var reset_handler = function() {
     $("#save").html("저장");
     $("#save").attr("disabled", true);
     $("#lint").html("XML lint 실행");
     $("#lint").attr("disabled", true);
     $("#extract").html("추출 실행");
     $("#extract").attr("disabled", true);
     $("#setacl").html("ACL 설정");
     $("#setacl").attr("disabled", true);
     $("#feedly_link").html("Feedly 등록");
     $("#feedly_link").attr("disabled", true); 
     $("#disable").html("비활성화");
     $("#disable").removeAttr("disabled");
     $("#remove").html("삭제");
     $("#remove").removeAttr("disabled");
 };

 // remove button event handler
 var remove_handler = function() {
     $("remove").html("삭제 중");
     var category_name = selected_category;
     var sample_feed = selected_sample_feed;
     $.post(
         ajax_url,
         { "command": "remove", "category_name": category_name, "sample_feed": sample_feed },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 $("#status").html(get_error_message(res["message"]));
             } else {
                 $("#status").html(get_success_message("삭제 성공"));
                 $("#remove").html("삭제 완료");
                 $("#remove").attr("disabled", true);
             }
         }
     );
 };

 // remove button event handler
 var disable_handler = function() {
     $("disable").html("비활성화 중");
     var category_name = selected_category;
     var sample_feed = selected_sample_feed;
     $.post(
         ajax_url,
         { "command": "disable", "category_name": category_name, "sample_feed": sample_feed },
         function(data, textStatus, jqXHR) {
             res = jQuery.parseJSON(data);
             if (res["result"] != "0") {
                 $("#status").html(get_error_message(res["message"]));
             } else {
                 $("#status").html(get_success_message("비활성화 성공"));
                 $("#disable").html("비활성화 완료");
                 $("#disable").attr("disabled", true);
             }
         }
     );
 };

 $("#feed_name").off("change").off("keydown").change(change_handler); 
 $("#feed_name").off("change").off("keydown").keydown(change_handler);
 $("#save").off("click").click(save_handler);
 $("#lint").off("click").click(lint_handler);
 $("#extract").off("click").click(extract_handler);
 $("#setacl").off("click").click(setAcl_handler);
 $("#reset").off("click").click(reset_handler);
 $("#remove").off("click").click(remove_handler);
 $("#disable").off("click").click(disable_handler);

 function read_form_cdata() {
     var cdata_arr = new Array();
     var cdata = $(".cdata");
     for (var i = 0; i < cdata.length; i++) {
         cdata_arr[i] = cdata[i].value;
     }
     return cdata_arr;
 }

 var StringBuffer = function() {
     this.buffer = new Array();
 }
 StringBuffer.prototype.append = function(obj) {
     this.buffer.push(obj);
 }
 StringBuffer.prototype.toString = function() {
     return this.buffer.join("");
 }

 function replace_form_with_cdata(cdata_arr) {
     var xml_text = $("#xml").html();
     // basic replacement
     var regex = new RegExp("<br>", "g");
     xml_text = xml_text.replace(regex, "");
     regex = new RegExp("&nbsp;", "g");
     xml_text = xml_text.replace(regex, " ");
     regex = new RegExp("\t", "g");
     xml_text = xml_text.replace(regex, "    ");
     regex = new RegExp("&lt;", "g");
     xml_text = xml_text.replace(regex, "<");
     regex = new RegExp("&gt;", "g");
     xml_text = xml_text.replace(regex, ">");

     // split
     var index = 0;
     var i = 0;
     var index_arr = new Array();
     var start_pattern_str = "<![CDATA[";
     var end_pattern_str = "]]>";
     while (1) {
         index = xml_text.indexOf(start_pattern_str, index);
         if (index < 0 || index >= xml_text.length) {
             break;
         }
         // 시작점
         index_arr.push(index);
         index = xml_text.indexOf(end_pattern_str, index);
         if (index < 0 || index >= xml_text.length) {
             break;
         }
         // 종료점
         index_arr.push(index);
     }
     // concatenate
     var new_xml_text = new StringBuffer();
     var start = 0;
     var j = 0;
     for (var i = 0; i < index_arr.length; i+=2) {
         // (시작점, 종료점) 두 개씩 꺼내서 그 가운데를 바꿔치기함
         var i0 = index_arr[i];
         var i1 = index_arr[i+1];
         new_xml_text.append(xml_text.substr(start, i0 + start_pattern_str.length - start));
         new_xml_text.append(cdata_arr[j++]);
         new_xml_text.append(xml_text.substr(i1, end_pattern_str.length));
         start = i1 + 3;
     }
     new_xml_text.append(xml_text.substr(start, xml_text.length - start));
     return new_xml_text.toString();
 }
</script>
