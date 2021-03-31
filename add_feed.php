<?php
require "common.php";

if (!is_client_local_ip()) {
    require dirname(__FILE__) . "/oauth/oauth_check.php";
}

$feed_dir = (array_key_exists("feed_dir", $_GET) ? $_GET{"feed_dir"} : "");
$feed_name = (array_key_exists("feed_name", $_GET) ? $_GET{"feed_name"} : "");
$feed_dir = determine_current_feed_dir($work_dir, $feed_name);
?>

<!DOCTYPE HTML>
<html>
    <?include("header.html");?>
    
    <body>
        <?include("menu.html");?>
        
        <script type="text/javascript">
         <?print_id2name_map($id2conf_map);?>
        </script>

        <div class="card" id="add_feed">
            <div class="card-header">
                카테고리: <!--select id="category_list" name="category_dir"-->
                <?foreach ($category_list as $k) {?>
                    <!--option name="<?=$k?>" value="<?=$k?>"><?=$k?></option-->
                <?}?>
                <!--/select-->
            </div>
            <div class="card-body">
                <div id="category_list">
                    <?foreach ($category_list as $k) {?>
                        <button type="button" class="btn btn-<?=($k[0] != '_' ? 'primary' : 'light')?>" onclick="select_category_handler('<?=$k?>');"><?=$k?></button>
                    <?}?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                샘플 피드: <!--select id="feed_list" name="feed_dir">
                                </select-->
            </div>
            <div class="card-body">
                <div id="feed_list"></div>
            </div>
        </div>

        <div class="card">
            <div id="xml" class="card-body">
            </div>
            
            <div class="card-body">
                <div>
                    <span>
                        새로운 Feed 이름: <input type='text' id='feed_name' name='feed_name' value=""/>.xml에
                        <button id='save' class="btn btn-success" disabled>저장</button>
                    </span>
                </div>
                <div>
                    <span>
                        <button id='lint' class="btn btn-success" disabled>XML lint 실행</button>
                        <button id='install' class="btn btn-success" disabled>설정파일 설치</button>
                        <button id='extract' class="btn btn-success" disabled>추출</button>
                        <button id='setacl' class="btn btn-success" disabled>ACL 설정</button>
                        <a href='#' class="btn btn-success" role="button" id='feedly_link' target="_blank">Inoreader 등록</a>
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

         var select_category_handler = function(category_name) {
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
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
                         sorted_list = id_list.sort(function(a, b) { try { return id2name_map[a].localeCompare(id2name_map[b]); } catch (e) { console.log(a); console.log(e); } });
                         html = "";
                         for (var i = 0; i < sorted_list.length; i++) {
                             var feed_id = sorted_list[i];
                             html += '<button type="button" class="btn btn-' + (feed_id[0] != '_' ? 'primary' : 'light') + '" onclick="select_feed_handler(\'' + category_name + '\', \''+ feed_id + '\');">' + id2name_map[feed_id] + '</button>\n';
                         }
                         $("#feed_list").html(html);
                         selected_category = category_name;
                     }
                 }
             );
             reset_handler();
         };

         var select_feed_handler = function(category_name, sample_feed_name) {
             console.log("selected_category=" + selected_category + ", sample_feed_name=" + sample_feed_name + ", feed_name=" + feed_name);
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
                         $("#feedly_link").attr("href", 'https://www.inoreader.com/feed/' + encodeURIComponent('https://terzeron.com/' + sample_feed_name + '.xml'));
                     }
                 }
             );
             reset_handler();
         };


         $(document).ready(function() {
             <?if ($feed_dir != "") {?>
             setTimeout(function() {
                 select_category_handler("<?=$feed_dir?>");
                 <?if ($feed_name != "") {?>
                 setTimeout(function() {
                     select_feed_handler("<?=$feed_dir?>", "<?=$feed_name?>");
                 }, 200);
                 <?}?>
             }, 100);
             <?}?>
         });

         // feed_name input change event handler
         var change_handler = function() {
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             if (selected_category && selected_sample_feed) {
                 $("#save").removeAttr("disabled");
             } else {
                 alert("샘플 피드를 선택해주세요.");
             }
         };
         
         // save button click event handler
         var save_handler = function() {
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name); 
            $("#save").html("저장 중");
             var feed_name = $("#feed_name").val();
             var category_name = selected_category;
             if (check_feed_name(feed_name) < 0) {
                 return -1;
             }
             var cdata_arr = read_form_cdata();
             var xml_text = replace_form_with_cdata(cdata_arr);
             $.post(
                 ajax_url,
                 { "command": "save", "feed_name": feed_name, "category_name": category_name, "sample_feed": selected_sample_feed, "xml_text": xml_text },
                 function(data, textStatus, jqXHR) {
                     res = jQuery.parseJSON(data);
                     if (res["result"] != "0") {
                         $("#status").html(get_error_message(res["message"]));
                     } else {
                         $("#status").html(get_success_message(feed_name + ".xml 파일 저장 성공"));
                         $("#save").html("저장 완료");
                         $("#save").attr("disabled", true);
                         $("#lint").removeAttr("disabled");
                         $("#feedly_link").attr("href", 'https://www.inoreader.com/feed/' + encodeURIComponent('https://terzeron.com/' + feed_name + '.xml'));
                     }
                 }
             );
         };

         // lint button event handler
         var lint_handler = function() { 
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             $("#lint").html("XML lint 실행 중");
             var feed_name = $("#feed_name").val();
             if (feed_name == "") {
                 feed_name = selected_sample_feed;
             }
             var category_name = selected_category;
             $.post(
                 ajax_url,
                 { "command": "lint", "feed_name": feed_name, "category_name": category_name, "sample_feed": selected_sample_feed },
                 function(data, textStatus, jqXHR) {
                     res = jQuery.parseJSON(data);
                     if (res["result"] != "0") {
                         $("#status").html(get_error_message(res["message"]));
                     } else {
                         $("#status").html(get_success_message("XML 검사 성공"));
                         $("#lint").html("XML lint 실행 완료");
                         $("#lint").attr("disabled", true);
                         $("#install").removeAttr("disabled");
                     }
                 }
             );
         };

         var install_handler = function() {
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             $("#install").html("설정파일 설치 중");
             var feed_name = $("#feed_name").val();
             if (feed_name == "") {
                 feed_name = selected_sample_feed;
             }
             var category_name = selected_category;
             $.post(
                 ajax_url,
                 { "command": "install", "feed_name": feed_name, "category_name": category_name, "sample_feed": selected_sample_feed },
                 function(data, textStatus, jqXHR) {
                     res = jQuery.parseJSON(data);
                     if (res["result"] != "0") {
                         $("#status").html(get_error_message(res["message"]));
                     } else {
                         $("#status").html(get_success_message("설정파일 설치 성공"));
                         $("#install").html("설정파일 설치 완료");
                         $("#install").attr("disabled", true);
                         $("#extract").removeAttr("disabled");
                     }
                 }
             );
         };

         // extract button event handler
         var extract_handler = function() {
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             $("#extract").html("추출 중");
             var feed_name = $("#feed_name").val();
             var category_name = selected_category;
             $.post(
                 ajax_url,
                 { "command": "extract", "feed_name": feed_name, "category_name": category_name, "sample_feed": selected_sample_feed },
                 function(data, textStatus, jqXHR) {
                     res = jQuery.parseJSON(data);
                     if (res["result"] != "0") {
                         $("#status").html(get_error_message(res["message"]));
                         $("#extract").html("재추출 시도");
                     } else {
                         $("#status").html(get_success_message("피드 추출 성공"));
                         $("#extract").html("추출 완료");
                         $("#extract").attr("disabled", true);
                     }
                 }
             );
         };

         // setacl button event handler
         var setacl_handler = function() {
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             $("setacl").html("ACL 설정 중");
             var feed_name = $("#feed_name").val();
             if (feed_name == "") {
                 feed_name = selected_sample_feed;
             }
             var category_name = selected_category;
             $.post(
                 ajax_url,
                 { "command": "setacl", "feed_name": feed_name, "category_name": category_name, "sample_feed": selected_sample_feed },
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
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             $("#save").html("저장");
             $("#save").attr("disabled", true);
             $("#lint").html("XML lint 실행");
             $("#lint").attr("disabled", true);
             $("#install").html("설정파일 설치");
             $("#install").attr("disabled", true);
             $("#extract").html("추출");
             $("#extract").removeAttr("disabled");
             $("#setacl").html("ACL 설정");
             $("#setacl").removeAttr("disabled");
             $("#feedly_link").html("Inoreader 등록");
             $("#feedly_link").removeAttr("disabled"); 
             $("#disable").html("비활성화");
             $("#disable").removeAttr("disabled");
             $("#remove").html("삭제");
             $("#remove").removeAttr("disabled");
         };

         // remove button event handler
         var remove_handler = function() {
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             $("remove").html("삭제 중");
             var category_name = selected_category;
             $.post(
                 ajax_url,
                 { "command": "remove", "category_name": category_name, "sample_feed": selected_sample_feed },
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
             console.log("selected_category=" + selected_category + ", selected_sample_feed=" + selected_sample_feed + ", feed_name=" + feed_name);
             $("disable").html("비활성화 중");
             var category_name = selected_category;
             $.post(
                 ajax_url,
                 { "command": "disable", "category_name": category_name, "sample_feed": selected_sample_feed },
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
         $("#install").off("click").click(install_handler);
         $("#extract").off("click").click(extract_handler);
         $("#setacl").off("click").click(setacl_handler);
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
    </body>
</html>
