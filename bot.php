<?php 
define('BOT_TOKEN', '5193842214:AAEIM8_1qI3UwkyyvClqK7Sb0BZnSr91lGw'); 
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

function apiRequestWebhook($method, $parameters) {
	if (!is_string($method)) {
		error_log("Nome do método deve ser uma string\n"); 
		return false; 
	} 
	if (!$parameters) {
		$parameters = array(); 
	} else if (!is_array($parameters)) {
		error_log("Os parâmetros devem ser um array\n"); 
		return false; 
	} 
	$parameters["method"] = $method; 
	header("Content-Type: application/json"); 
	echo json_encode($parameters); 
	return true; 
} 
function exec_curl_request($handle) {
	$response = curl_exec($handle); 
	if ($response === false) {
		$errno = curl_errno($handle); 
		$error = curl_error($handle); 
		error_log("Curl retornou um erro $errno: $error\n"); 
		curl_close($handle); 
		return false; 
	} 
	$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE)); 
	curl_close($handle); 
	if ($http_code >= 500) {
		// do not wat to DDOS server if something goes wrong 
		sleep(10); 
		return false; 
	} else if ($http_code != 200) {
		$response = json_decode($response, true); 
		error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n"); 
		if ($http_code == 401) {
			throw new Exception('Invalid access token provided'); 
		} 
		return false; 
	} else {
		$response = json_decode($response, true); 
		if (isset($response['description'])) {
			error_log("Request was successfull: {$response['description']}\n"); 
		} 
		$response = $response['result']; 
	} 
	return $response; 
} 
function apiRequest($method, $parameters) {
	if (!is_string($method)) {
		error_log("Method name must be a string\n"); 
		return false; 
	} 
	if (!$parameters) {
		$parameters = array(); 
	} else if (!is_array($parameters)) {
		error_log("Parameters must be an array\n"); 
		return false; 
	} 
	foreach ($parameters as $key => &$val) {
		// encoding to JSON array parameters, for example reply_markup 
		if (!is_numeric($val) && !is_string($val)) {
			$val = json_encode($val); 
		} 
	} 
	$url = API_URL.$method.'?'.http_build_query($parameters); 
	$handle = curl_init($url); 
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5); 
	curl_setopt($handle, CURLOPT_TIMEOUT, 60); 
	return exec_curl_request($handle); 
} 
function apiRequestJson($method, $parameters) {
	if (!is_string($method)) {
		error_log("Method name must be a string\n"); 
		return false; 
	} 
	if (!$parameters) {
		$parameters = array(); 
	} else if (!is_array($parameters)) {
		error_log("Parameters must be an array\n"); 
		return false; 
	} 
	$parameters["method"] = $method; 
	$handle = curl_init(API_URL); 
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5); 
	curl_setopt($handle, CURLOPT_TIMEOUT, 60); 
	curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters)); 
	curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
	return exec_curl_request($handle); 
} 
function processaCallbackQuery($callback){
  $BOT_TOKEN = "5193842214:AAEIM8_1qI3UwkyyvClqK7Sb0BZnSr91lGw";
  $callback_id = $callback['id'];
  $chat_id = $callback['message']['chat']['id'];
  $type = $callback['message']['chat']['type'];
  $message_id = $callback['message']['message_id'];
  $user_id = $callback['from']['id'];
  $user_name = $callback['from']['username']; 
  $name = $callback['from']['first_name'];
  $data =  $callback['data'];
  $data_array=unserialize($data);
  $anterior = $message_id - 1;
  $adm = "1484706029";
  $adm2 = "902983459";
  
  if($data_array['data']=="apagar") {
     if($data_array['id']==$callback['from']['id']) {      
         apiRequest("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $message_id));
         apiRequest("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $anterior));
         
     } else {
  
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));
      
     }
  } else if ($data == "apagar") {    
    apiRequest("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $message_id));
    apiRequest("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $anterior));

  } else if (strpos($data, "ban") === 0) {

       $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
       $json = json_encode($getAdmins, true);
                                                                                                                                      
       function getStr($string,$start,$end){
	       $str = explode($start,$string);
	       $str = explode($end,$str[1]);
	       return $str[0];
       }
                   
      $status = getStr($json,'status":"','"');
      $permissao = getStr($json,'can_restrict_members":',',');                             
 
      if($status == 'creator' || $permissao == 'true') {     
          $id = explode(' ', $data);
          apiRequest('kickChatMember', array('chat_id' => $chat_id, 'user_id' => $id[1]));                    
          if($id[3]) {
              apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[@$id[3]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: BANIDO! 🚷*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Desbanir  ✅',"callback_data"=>"unban $id[1] $id[2] $id[3]")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

          }else{
                    apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[$id[2]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: BANIDO! 🚷*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Desbanir  ✅',"callback_data"=>"unban $id[1] $id[2]")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

          }            
      }else {
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "📛 Você precisa ser um 👮🏻 Admin ou 👷🏻‍♂ Moderador e precisa ter permissão para realizar essa ação", "show_alert" => true));
      }

  } else if (strpos($data, "unban") === 0) {

      $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
      $json = json_encode($getAdmins, true);
                                                                                                                                      
       function getStr($string,$start,$end){
	       $str = explode($start,$string);
	       $str = explode($end,$str[1]);
	       return $str[0];
       }
                   
      $status = getStr($json,'status":"','"');
      $permissao = getStr($json,'can_restrict_members":',',');                              
 
      if($status == 'creator' || $permissao == 'true') {        
          $id = explode(' ', $data);
  	    apiRequest('unbanChatMember', array('chat_id' => $chat_id, 'user_id' => $id[1]));          
          if($id[3]) {
              apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[@$id[3]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: BANIDO! 🚷*

*~ DESBANIDO! ✅*", "reply_to_message_id" => $message_id)); 
 
          }else{
              apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[$id[2]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: BANIDO! 🚷*

*~ DESBANIDO! ✅*", "reply_to_message_id" => $message_id)); 

          }
      } else {
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "📛 Você precisa ser um 👮🏻 Admin ou 👷🏻‍♂ Moderador e precisa ter permissão para realizar essa ação", "show_alert" => true));
      }
      
  } else if (strpos($data, "mute") === 0) {

       $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
       $json = json_encode($getAdmins, true);
                                                                                                                                      
       function getStr($string,$start,$end){
	       $str = explode($start,$string);
	       $str = explode($end,$str[1]);
	       return $str[0];
       }
                   
      $status = getStr($json,'status":"','"');
      $permissao = getStr($json,'can_restrict_members":',',');                              
 
      if($status == 'creator' || $permissao == 'true') {     
          $id = explode(' ', $data);
          apiRequest('restrictChatMember', array('chat_id' => $chat_id, 'user_id' => $id[1]));                    
          if($id[3]) {
              apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[@$id[3]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: SILENCIADO! 🔇*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Dessilenciar  ✅',"callback_data"=>"unmute $id[1] $id[2] $id[3]")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

          }else{
                    apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[$id[2]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: SILENCIADO! 🔇*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Dessilenciar  ✅',"callback_data"=>"unmute $id[1] $id[2]")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

           }            
       }else {
           apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "📛 Você precisa ser um 👮🏻 Admin ou 👷🏻‍♂ Moderador e precisa ter permissão para realizar essa ação", "show_alert" => true));
       }

  } else if (strpos($data, "unmute") === 0) {

       $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
       $json = json_encode($getAdmins, true);
                                                                                                                                      
       function getStr($string,$start,$end){
	       $str = explode($start,$string);
	       $str = explode($end,$str[1]);
	       return $str[0];
       }
                   
      $status = getStr($json,'status":"','"');
      $permissao = getStr($json,'can_restrict_members":',',');                              
 
      if($status == 'creator' || $permissao == 'true') {          
          $id = explode(' ', $data);
  	    apiRequest('restrictChatMember', array('chat_id' => $chat_id, 'user_id' => $id[1], 'can_send_messages' => '1', 'can_send_media_messages' => '1', 'can_send_other_messages' => '1', 'can_add_web_page_previews' => '1'));          
          if($id[3]) {
              apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[@$id[3]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: SILENCIADO! 🔇*

*~ DESSILENCIADO! ✅*", "reply_to_message_id" => $message_id)); 
 
          }else{
              apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "[$id[2]](tg://user?id=$id[1]) *[*`".$id[1]."`*]*
*Ação: SILENCIADO! 🔇*

*~ DESSILENCIADO! ✅*", "reply_to_message_id" => $message_id)); 

          }
      } else {
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "📛 Você precisa ser um 👮🏻 Admin ou 👷🏻‍♂ Moderador e precisa ter permissão para realizar essa ação", "show_alert" => true));
      }
   
  } else if ($data == 'menu') {
       if($type == 'private') {
      
      apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '✅ *MENU DE COMANDOS*

*Escolha uma das opções a baixo e clique no botão correspondente.*',
		    'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                               //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )

                                            )
                                    )));

       } else {
           
           apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '✅ *MENU DE COMANDOS*

*Escolha uma das opções a baixo e clique no botão correspondente.*',
		    'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                            
                                              //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )                                                 
                                            )
                                    )));

       }
    
   }else if($data_array['data']=="adm") {
       if($data_array['id']==$callback['from']['id']) {  

           $getAdmins = apiRequest('getChatAdministrators', array('chat_id' => $chat_id));
           $json = json_encode($getAdmins, true);
 
           $listAdmins = explode('username":"', $json);
           for ($i=1; $i < count($listAdmins); $i++) { 
               $admins = explode('"', $listAdmins[$i]);
               $adms = $adms." ".$admins[0];
           }
                
          if(!stripos($adms, "VexedTutoriaisbot")) {         
              apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'show_alert' => true, 'text' => "OPS‼️...

Parece que eu ainda não sou administrador do grupo...
Para continuar me coloque como administrador. 😉"));             
          }else{              
              apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "✅ Excelente! :)

Agora clique no botão do Menu para conhecer todos os meus comandos!",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔘  Menu  🔘',"callback_data"=>'menu'), //botão 1                                                 
                                                      )
                                                          
                                            )
                                    )));
          }
       } else {
  
           apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }

   } else if ($data == 'queroservip') {

       include("botcon/planos.php");
       
   } else if ($data == 'vipprivado') {
    apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '
*💎 𝗧𝗔𝗕𝗘𝗟𝗔 𝗗𝗘 𝗣𝗥𝗘𝗖̧𝗢𝗦 💎

✅  1 SEMANA  20 R$
✅  2 SEMANAS 35 R$
✅  1 MES 60 R$ 
✅  2 MESES  110 R$

♣️ 𝘊𝘖𝘕𝘚𝘜𝘓𝘛𝘈𝘚 𝘐𝘓𝘐𝘔𝘐𝘛𝘈𝘋𝘈𝘚 ♣️*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                     //linha 1
                                                     array(
                                                         array('text'=>'CONTATO DO VENDEDOR','url'=>'https://t.me/vexedoficial')
                                                      )
                                                          
                                            )
                                    )));

  } else if ($data == 'vipgrupo') {
    apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '
*💎 𝙏𝘼𝘽𝙀𝙇𝘼 𝘿𝙀 𝙋𝙍𝙀𝘾̧𝙊𝙎 𝙂𝙍𝙐𝙋𝙊 𝙑𝙄𝙋 💎

✅ 07 DIAS  15 R$ 
✅ 15 DIAS  35 R$ 
✅ 1 MES  90 R$*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                     //linha 1
                                                     array(
                                                         array('text'=>'CONTATO DO VENDEDOR','url'=>'https://t.me/vexedoficial')
                                                      )                                                     
                                                          
                                            )
                                    )));
       
   } else if ($data == 'checkers') {            
       apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '⚙ *CHECKERS* 
*━━━━━━━━━━━━━━━━━━*
*[+] GG:* `/chkgg` *número|mês|ano|cvv*
*━━━━━━━━━━━━━━━━━━*
*[+] Combate:* `/combate` *email|senha*
*━━━━━━━━━━━━━━━━━━*
*[+] GloboSat:* `/globosat` *email|senha*
*━━━━━━━━━━━━━━━━━━*
*[+] Premiere:* `/premiere` *email|senha*
*━━━━━━━━━━━━━━━━━━*
*[+] Sexy Hot:* `/sexyhot` *email|senha*
*━━━━━━━━━━━━━━━━━━*
*[+] Telecine:* `/telecine` *email|senha*
*━━━━━━━━━━━━━━━━━━*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔙  Voltar  🔙',"callback_data"=>'menu'), //botão 1                                                                                                     
                                                      )
                                                          
                                            )
                                    )));

  } else if ($data == 'consultas') {
    apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '🔍 *MENU DE CONSULTAS*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔍 CONSULTAS VIP 🔍',"callback_data"=>'consultasvip'), //botão 1                                              
                                                      ),
                                                       //linha 2
                                                     array(
                                                         array('text'=>'🔍 CONSULTAS GRÁTIS 🔍',"callback_data"=>'consultasfree'), //botão 1                                              
                                                      ),                                                      
                                                       //linha 3
                                                     array(
                                                         array('text'=>'🔙  Voltar  🔙',"callback_data"=>'menu'), //botão 1                                              
                                                      )
                                                          
                                            )
                                    )));
                                    
    } else if ($data == 'consultasvip') {
    apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '🔍 *CONSULTAS VIP*
*━━━━━━━━━━━━━━━━━━*
*[+] CPF2:* `/cpf2 27867260854`
*━━━━━━━━━━━━━━━━━━*
*[+] CNPJ:* `/cnpj 27865757000102`
*━━━━━━━━━━━━━━━━━━*
*[+] EMAIL:* `/email andreia@yahoo.com`
*━━━━━━━━━━━━━━━━━━*
*[+] NOME:* `/nome Tania Mara Moyses`
*━━━━━━━━━━━━━━━━━━*
*[+] PARENTES:* `/parentes 27867260854`
*━━━━━━━━━━━━━━━━━━*
*[+] PLACA:* `/placa OGT0458`
*━━━━━━━━━━━━━━━━━━*
*[+] VIZINHOS:* `/vizinhos 27867260854`
*━━━━━━━━━━━━━━━━━━*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔙  Voltar  🔙',"callback_data"=>'consultas'), //botão 1                                              
                                                      )
                                                          
                                            )
                                    )));

  } else if ($data == 'consultasfree') {
    apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '🔍 *CONSULTAS GRÁTIS*
*━━━━━━━━━━━━━━━━*
*[+] BIN:* `/bin 498408`
*━━━━━━━━━━━━━━━━*
*[+] CEP:* `/cep 70040010`
*━━━━━━━━━━━━━━━━*
*[+] CPF1:* `/cpf1 27867260854`
*━━━━━━━━━━━━━━━━*
*[+] IP:* `/ip 204.152.203.157`
*━━━━━━━━━━━━━━━━*
*[+] SITE:* `/site google.com`
*━━━━━━━━━━━━━━━━*
*[+] TELEFONE:* `/tel 51995379721`
*━━━━━━━━━━━━━━━━*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔙  Voltar  🔙',"callback_data"=>'consultas'), //botão 1                                              
                                                      )
                                                          
                                            )
                                    )));            
                                    
   } else if ($data == 'comandosgrupos') {
      apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '🔆 *COMANDOS PARA GRUPO

👥 Disponível para todos o usuários
👮🏻 Disponível para Admins

━━━━━━━━━━━━━━━━━━
• Usuários*

👥 `/id`  *fornece informações sobre o próprio usuário.*

👥 `/link`  *fornece link do grupo.*

*━━━━━━━━━━━━━━━━━━
• Mensagens Fixadas*

👮🏻 `/pin`  *fixa mensagem respondida.*

👮🏻 `/unpin`  *remove a mensagem fixada.*

*━━━━━━━━━━━━━━━━━━
• Administração*

👮🏻 `/info`  *fornece informações sobre o usuário.*

👮🏻 `/infopvt`  *mesma função do* `/info`*, porém envia as informações no chat privado.*

👮🏻 `/ban`  *banir usuário do grupo sem possibilidade de retorno atravéz do link do grupo.*

👮🏻 `/mute`  *permite que um usuário leia as mensagens porém não pode escrever no grupo.*

👮🏻 `/unban`  *usado com usuários banidos, permitindo o retorno atravéz do link do grupo.*

👮🏻 `/unmute`  *usado com usuários silenciados, permitindo que o usuário volte a escrever no grupo.*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔙  Voltar  🔙',"callback_data"=>'menu'), //botão 4                                                      
                                                      ),
                                                      //linha 2
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

  } else if ($data == 'geradores') {
      apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => '⚙ *GERADORES*
*━━━━━━━━━━━━━━━━━*
*[+] Gerador de CPF:* /gerarcpf
*━━━━━━━━━━━━━━━━━*
*[+] Contas Bancárias:* /gerarcontas
*━━━━━━━━━━━━━━━━━*
*[+] Dados de Pessoas:* /gerardados 
*━━━━━━━━━━━━━━━━━*
*[+] Dados de Empresas:* /empresas 
*━━━━━━━━━━━━━━━━━*',
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔙  Voltar  🔙',"callback_data"=>'menu'), //botão 1                                              
                                                      )
                                                          
                                            )
                                    )));

   } else if ($data_array['data']=="gerardadosdemulher") {
       if($data_array['id']==$callback['from']['id']) { 

$idades = array('20', '25', '30', '35', '40', '45', '50', '55');
$idade = $idades[mt_rand(0, sizeof($idades) - 1)];

$ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,"https://www.4devs.com.br/ferramentas_online.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,"acao=gerar_pessoa&cep_cidade=&cep_estado=&idade=$idade&pontuacao=S&sexo=M");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    curl_close ($ch);

    $json_r = json_decode($output);
 
$pai = explode(' ', $json_r->pai);

if($pai[6] == "") {
	$sobrenome = $pai[5];
} 
if($pai[5] == "") {
	$sobrenome = $pai[4];
} 
if($pai[4] == "") {
	$sobrenome = $pai[3];
} 
if($pai[3] == "") {
	$sobrenome = $pai[2];
} 
if($pai[2] == "") {
	$sobrenome = $pai[1];
} 
if($pai[5] == "da" || $pai[5] == "de" || $pai[5] == "do" || $pai[5] == "dos") {
    $sobrenome = $pai[5]." ".$sobrenome;
}
if($pai[4] == "da" || $pai[4] == "de" || $pai[4] == "do" || $pai[4] == "dos") {
    $sobrenome = $pai[4]." ".$sobrenome;
}
if($pai[3] == "da" || $pai[3] == "de" || $pai[3] == "do" || $pai[3] == "dos") {
    $sobrenome = $pai[3]." ".$sobrenome;
}
if($pai[2] == "da" || $pai[2] == "de" || $pai[2] == "do" || $pai[2] == "dos") {
    $sobrenome = $pai[2]." ".$sobrenome;
}
if($pai[1] == "da" || $pai[1] == "de" || $pai[1] == "do" || $pai[1] == "dos") {
    $sobrenome = $pai[1]." ".$sobrenome;
}
           
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "⚙ *GERADOR DE PESSOAS* ⚙

*• NOME:*  `$json_r->nome`
*• DT DE NASC:*  `$json_r->data_nasc`
*• CPF:*  `$json_r->cpf`
*• RG:*  `$json_r->rg`
*• MÃE:*  `$json_r->mae $sobrenome`
*• PAI:*  `$json_r->pai`
*• ALTURA:*  `$json_r->altura`
*• PESO:*  `$json_r->peso`
*• TIPO SANG:*  `$json_r->tipo_sanguineo`
*• SIGNO:*  `$json_r->signo`

*• ENDEREÇO:*  `$json_r->endereco`
*• NÚMERO:*  `$json_r->numero`
*• BAIRRO:*  `$json_r->bairro`
*• CIDADE:*  `$json_r->cidade`
*• ESTADO:*  `$json_r->estado`
*• CEP:*  `$json_r->cep`
*• TELEFONE:*  `$json_r->telefone_fixo`
*• CELULAR:*  `$json_r->celular`
*• EMAIL:*  `$json_r->email`
*• SENHA:*  `$json_r->senha`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔁  Atualizar  🔁',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'gerardadosdemulher']))//botão com callback                                                   
                                                      ),
                                                     //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
     } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="gerardadosdehomem") {
    if($data_array['id']==$callback['from']['id']) { 


$idades = array('20', '25', '30', '35', '40', '45', '50', '55');
$idade = $idades[mt_rand(0, sizeof($idades) - 1)];

$ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,"https://www.4devs.com.br/ferramentas_online.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,"acao=gerar_pessoa&cep_cidade=&cep_estado=&idade=$idade&pontuacao=S&sexo=H");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    curl_close ($ch);

    $json_r = json_decode($output);
  
$pai = explode(' ', $json_r->pai);

if($pai[6] == "") {
	$sobrenome = $pai[5];
} 
if($pai[5] == "") {
	$sobrenome = $pai[4];
} 
if($pai[4] == "") {
	$sobrenome = $pai[3];
} 
if($pai[3] == "") {
	$sobrenome = $pai[2];
} 
if($pai[2] == "") {
	$sobrenome = $pai[1];
} 
if($pai[5] == "da" || $pai[5] == "de" || $pai[5] == "do" || $pai[5] == "dos") {
    $sobrenome = $pai[5]." ".$sobrenome;
}
if($pai[4] == "da" || $pai[4] == "de" || $pai[4] == "do" || $pai[4] == "dos") {
    $sobrenome = $pai[4]." ".$sobrenome;
}
if($pai[3] == "da" || $pai[3] == "de" || $pai[3] == "do" || $pai[3] == "dos") {
    $sobrenome = $pai[3]." ".$sobrenome;
}
if($pai[2] == "da" || $pai[2] == "de" || $pai[2] == "do" || $pai[2] == "dos") {
    $sobrenome = $pai[2]." ".$sobrenome;
}
if($pai[1] == "da" || $pai[1] == "de" || $pai[1] == "do" || $pai[1] == "dos") {
    $sobrenome = $pai[1]." ".$sobrenome;
}
          
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "⚙ *GERADOR DE PESSOAS* ⚙

*• NOME:*  `$json_r->nome`
*• DT DE NASC:*  `$json_r->data_nasc`
*• CPF:*  `$json_r->cpf`
*• RG:*  `$json_r->rg`
*• MÃE:*  `$json_r->mae $sobrenome`
*• PAI:*  `$json_r->pai`
*• ALTURA:*  `$json_r->altura`
*• PESO:*  `$json_r->peso`
*• TIPO SANG:*  `$json_r->tipo_sanguineo`
*• SIGNO:*  `$json_r->signo`

*• ENDEREÇO:*  `$json_r->endereco`
*• NÚMERO:*  `$json_r->numero`
*• BAIRRO:*  `$json_r->bairro`
*• CIDADE:*  `$json_r->cidade`
*• ESTADO:*  `$json_r->estado`
*• CEP:*  `$json_r->cep`
*• TELEFONE:*  `$json_r->telefone_fixo`
*• CELULAR:*  `$json_r->celular`
*• EMAIL:*  `$json_r->email`
*• SENHA:*  `$json_r->senha`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔁  Atualizar  🔁',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'gerardadosdehomem']))//botão com callback                                                   
                                                      ),
                                                     //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
     } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }

  } else if($data_array['data']=="homem") {
      if($data_array['id']==$callback['from']['id']) {
       
          include("botger/dados/estadoh.php");

      } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }
   } else if($data_array['data']=="mulher") {
       if($data_array['id']==$callback['from']['id']) {
                
           include("botger/dados/estadom.php");

       } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }

  } else if ($data_array['data']=="ACH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));
         
            }
        } else if ($data_array['data']=="ALH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="APH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AMH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="BAH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="CEH") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
       } else if ($data_array['data']=="DFH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ESH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="GOH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="MAH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MTH") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/dados/gerdadosh.php");
             } else {
  
                apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

             }
         } else if ($data_array['data']=="MSH") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/dados/gerdadosh.php");
             } else {
  
                apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

             }
         } else if ($data_array['data']=="MGH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PAH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PBH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PRH") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/dados/gerdadosh.php");
             } else {
  
                apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

             }
         } else if ($data_array['data']=="PEH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PIH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RJH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RNH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RSH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="ROH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RRH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SCH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SPH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";                
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SEH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="TOH") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/dados/gerdadosh.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
} else if ($data_array['data']=="ACM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ALM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="APM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AMM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="BAM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="CEM") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/dados/gerdadosm.php");
           } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="DFM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ESM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="GOM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="MAM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MTM") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/dados/gerdadosm.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

             }
         } else if ($data_array['data']=="MSM") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/dados/gerdadosm.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MGM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PAM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PBM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PRM") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/dados/gerdadosm.php");
             } else {
  
                apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PEM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PIM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RJM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RNM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RSM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="ROM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RRM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SCM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SPM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SEM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="TOM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/dados/gerdadosm.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }

  } else if($data_array['data']=="brasil") {
    if($data_array['id']==$callback['from']['id']) {
        
        $banco = "1";
        include("botger/contas/estado.php");

     } else {
  
        apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

     }
   } else if($data_array['data']=="bradesco") {
    if($data_array['id']==$callback['from']['id']) {
        
        $banco = "2";
        include("botger/contas/estado2.php");

     } else {
  
        apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

     }
   } else if($data_array['data']=="caixa") {
    if($data_array['id']==$callback['from']['id']) {
        
        $banco = "3";
        include("botger/contas/estado3.php");

     } else {
  
        apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

     }
  } else if($data_array['data']=="itau") {
    if($data_array['id']==$callback['from']['id']) {
        
        $banco = "4";
        include("botger/contas/estado4.php");

     } else {
  
        apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

     }
  } else if($data_array['data']=="santander") {
    if($data_array['id']==$callback['from']['id']) {
        
        $banco = "5";
        include("botger/contas/estado5.php");

     } else {
  
        apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

     }
} else if ($data_array['data']=="AC") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AL") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AP") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AM") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="BA") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="CE") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/contas/gerbanco.php");
           } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="DF") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ES") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="GO") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="MA") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MT") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/contas/gerbanco.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MS") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/contas/gerbanco.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MG") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PA") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PB") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PR") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/contas/gerbanco.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PI") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RJ") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RN") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RS") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RO") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RR") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SC") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SP") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="TO") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/contas/gerbanco.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
} else if ($data_array['data']=="AC2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AL2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AP2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AM2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="BA2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="CE2") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/contas/gerbanco2.php");
           } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="DF2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ES2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="GO2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="MA2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MT2") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/contas/gerbanco2.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MS2") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/contas/gerbanco2.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MG2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PA2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PB2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PR2") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/contas/gerbanco2.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PE2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PI2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RJ2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RN2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RS2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RO2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RR2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SC2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SP2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SE2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="TO2") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/contas/gerbanco2.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
} else if ($data_array['data']=="AC3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AL3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AP3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AM3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="BA3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="CE3") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/contas/gerbanco3.php");
           } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="DF3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ES3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="GO3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="MA3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MT3") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/contas/gerbanco3.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MS3") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/contas/gerbanco3.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MG3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PA3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PB3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PR3") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/contas/gerbanco3.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PE3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PI3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RJ3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RN3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RS3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RO3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RR3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SC3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SP3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SE3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="TO3") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/contas/gerbanco3.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
} else if ($data_array['data']=="AC4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AL4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AP4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AM4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="BA4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="CE4") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/contas/gerbanco4.php");
           } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="DF4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ES4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="GO4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="MA4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MT4") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/contas/gerbanco4.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MS4") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/contas/gerbanco4.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MG4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PA4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PB4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PR4") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/contas/gerbanco4.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PE4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PI4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RJ4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RN4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RS4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RO4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RR4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SC4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SP4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SE4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="TO4") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/contas/gerbanco4.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
} else if ($data_array['data']=="AC5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AL5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AP5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="AM5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="BA5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="CE5") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/contas/gerbanco5.php");
           } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
       } else if ($data_array['data']=="DF5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="ES5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="GO5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="MA5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MT5") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/contas/gerbanco5.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MS5") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/contas/gerbanco5.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="MG5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PA5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PB5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PR5") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/contas/gerbanco5.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PE5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="PI5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RJ5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RN5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RS5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RO5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="RR5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SC5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SP5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
         } else if ($data_array['data']=="SER5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
        } else if ($data_array['data']=="TO5") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/contas/gerbanco5.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

            }
  } else if ($data_array['data']=="ACE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AC";                
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="ALE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AL";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="APE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AP";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="AME") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "AM";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="BAE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "BA";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
       } else if ($data_array['data']=="CEE") {
           if($data_array['id']==$callback['from']['id']) {
               $estado = "CE";
               include("botger/empresa/gerarempresa.php");
           } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
       } else if ($data_array['data']=="DFE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "DF";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="ESE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "ES";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="GOE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "GO";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="MAE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MA";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="MTE") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MT";
                 include("botger/empresa/gerarempresa.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="MSE") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "MS";
                 include("botger/empresa/gerarempresa.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="MGE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "MG";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="PAE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PA";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="PBE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PB";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="PRE") {
             if($data_array['id']==$callback['from']['id']) {
                 $estado = "PR";
                 include("botger/empresa/gerarempresa.php");
             } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="PEE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PE";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="PIE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "PI";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="RJE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RJ";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="RNE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RN";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="RSE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RS";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="ROE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RO";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="RRE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "RR";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="SCE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SC";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="SPE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SP";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
         } else if ($data_array['data']=="SEE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "SE";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }
        } else if ($data_array['data']=="TOE") {
            if($data_array['id']==$callback['from']['id']) {
                $estado = "TO";
                include("botger/empresa/gerarempresa.php");
            } else {
  
               apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

           }

  } else if ($data_array['data']=="cpfgen10") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Gerando..."));
         for ($i=0; $i < 10; $i++) { 
                
               $ch = curl_init();

               curl_setopt($ch, CURLOPT_URL,"https://www.4devs.com.br/ferramentas_online.php");
               curl_setopt($ch, CURLOPT_POST, 1);
               curl_setopt($ch, CURLOPT_POSTFIELDS,"acao=gerar_cpf&pontuacao=S&cpf_estado=");
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

               $cpfgen = curl_exec($ch);

               curl_close ($ch);

               $unidade = "*CPF:*  `".$cpfgen."`";

               $titulos = $titulos."\n".$unidade;                

           }            

           if($cpfgen == ""){

           apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "⚙   *GERADOR DE CPF*   ⚙

*• NÃO FOI POSSÍVEL GERAR!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                                                                         
                                                     //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

           }else{

           apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "*Quantidade: 10* 
".$titulos,
           'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                       //linha 1
                                                         array(
                                                             array('text'=>' 10 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen10'])), //botão 1                                                                                                                  
                                                             array('text'=>' 30 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen30'])), //botão 2
                                                             array('text'=>' 50 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen50'])) //botão 3                                                                                                                                                                       
                                                          ),
                                                          //linha 2
                                                         array(
                                                             array('text'=>'🔁  Atualizar  🔁',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen10']))//botão com callback                                                   
                                                          ),                                                          
                                                           //linha 3
                                                         array(
                                                             array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                                                                      
                                                         )
                                                          
                                            )
                                    )));

          }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="cpfgen30") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Gerando..."));
         for ($i=0; $i < 30; $i++) { 
                
               $ch = curl_init();

               curl_setopt($ch, CURLOPT_URL,"https://www.4devs.com.br/ferramentas_online.php");
               curl_setopt($ch, CURLOPT_POST, 1);
               curl_setopt($ch, CURLOPT_POSTFIELDS,"acao=gerar_cpf&pontuacao=S&cpf_estado=");
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

               $cpfgen = curl_exec($ch);

               curl_close ($ch);

               $unidade = "*CPF:*  `".$cpfgen."`";

               $titulos = $titulos."\n".$unidade;                

           }            

           if($cpfgen == ""){

           apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "⚙   *GERADOR DE CPF*   ⚙

*• NÃO FOI POSSÍVEL GERAR!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                                                                         
                                                     //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

           }else{
        
           apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "*Quantidade: 30* 
".$titulos,
           'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                       //linha 1
                                                         array(
                                                             array('text'=>' 10 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen10'])), //botão 1                                                                                                                  
                                                             array('text'=>' 30 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen30'])), //botão 2
                                                             array('text'=>' 50 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen50'])) //botão 3                                                                                                                                                                      
                                                          ),
                                                          //linha 2
                                                         array(
                                                             array('text'=>'🔁  Atualizar  🔁',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen30']))//botão com callback                                                   
                                                          ),                                                          
                                                           //linha 3
                                                         array(
                                                             array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                                                                      
                                                         )
                                                          
                                            )
                                    )));
          
          }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="cpfgen50") {    
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Gerando..."));
         for ($i=0; $i < 50; $i++) { 
                
               $ch = curl_init();

               curl_setopt($ch, CURLOPT_URL,"https://www.4devs.com.br/ferramentas_online.php");
               curl_setopt($ch, CURLOPT_POST, 1);
               curl_setopt($ch, CURLOPT_POSTFIELDS,"acao=gerar_cpf&pontuacao=S&cpf_estado=");
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

               $cpfgen = curl_exec($ch);

               curl_close ($ch);

               $unidade = "*CPF:*  `".$cpfgen."`";

               $titulos = $titulos."\n".$unidade;                

           }            

           if($cpfgen == ""){

           apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "⚙   *GERADOR DE CPF*   ⚙

*• NÃO FOI POSSÍVEL GERAR!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                                                                         
                                                     //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

           }else{
           
           apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "*Quantidade: 50* 
".$titulos,
           'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                       //linha 1
                                                         array(
                                                             array('text'=>' 10 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen10'])), //botão 1                                                                                                                  
                                                             array('text'=>' 30 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen30'])), //botão 2
                                                             array('text'=>' 50 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen50'])) //botão 3                                                                                                                                                                       
                                                          ),
                                                          //linha 2
                                                         array(
                                                             array('text'=>'🔁  Atualizar  🔁',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen50']))//botão com callback                                                   
                                                          ),                                                          
                                                           //linha 3
                                                         array(
                                                             array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                                                                      
                                                         )
                                                          
                                            )
                                    )));    

          }
      } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

      }                               
 } else if ($data == 'proxy') {
           
     include("proxy/pais.php");
      
 } else if($data_array['data']=="HTTPBR") {
      if($data_array['id']==$callback['from']['id']) {
       
          include("proxy/quantidadehbr.php");

      } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }
   } else if($data_array['data']=="SOCKS4BR") {
       if($data_array['id']==$callback['from']['id']) {
                
           include("proxy/quantidades4br.php");

       } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }

} else if($data_array['data']=="SOCKS5BR") {
      if($data_array['id']==$callback['from']['id']) {
       
          include("proxy/quantidades5br.php");

      } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }
 
 } else if($data_array['data']=="HTTPUS") {
      if($data_array['id']==$callback['from']['id']) {
       
          include("proxy/quantidadehus.php");

      } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }
   } else if($data_array['data']=="SOCKS4US") {
       if($data_array['id']==$callback['from']['id']) {
                
           include("proxy/quantidades4us.php");

       } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }

} else if($data_array['data']=="SOCKS5US") {
      if($data_array['id']==$callback['from']['id']) {
       
          include("proxy/quantidades5us.php");

      } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }

} else if($data_array['data']=="proxybrasil") {
      if($data_array['id']==$callback['from']['id']) {
       
          include("proxy/tipobr.php");

      } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }
   } else if($data_array['data']=="estadosunidos") {
       if($data_array['id']==$callback['from']['id']) {
                
           include("proxy/tipous.php");

       } else {
  
          apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

       }

  } else if ($data_array['data']=="proxyHBR10") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=http&anon=elite&country=BR");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 10) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP*
`$titulos`
*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
} else if ($data_array['data']=="proxyHBR20") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=http&anon=elite&country=BR");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 20) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP*
`$titulos`
*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyHBR30") {    
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=http&anon=elite&country=BR");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 30) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY HTTP*
`$titulos`
*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));     

    }
    } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
} else if ($data_array['data']=="proxyHUS10") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=http&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 10) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyHUS20") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=http&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 20) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyHUS30") {    
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=http&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 30) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY HTTP*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));     

    }
    } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
} else if ($data_array['data']=="proxyS4BR10") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks4&anon=elite&country=BR");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 10) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyS4BR20") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks4&anon=elite&country=BR");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 20) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyS4BR30") {    
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks4&anon=elite&country=BR");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 30) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇧🇷  *PROXY SOCKS4*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));     

    }
    } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
} else if ($data_array['data']=="proxyS4US10") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks4&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 10) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyS4US20") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks4&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 20) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺??  *PROXY SOCKS4*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyS4US30") {    
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks4&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 30) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS4*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));     

     }
     } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
} else if ($data_array['data']=="proxyS5US10") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks5&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 10) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyS5US20") {
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks5&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 20) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

    }
  } else if ($data_array['data']=="proxyS5US30") {    
    if($data_array['id']==$callback['from']['id']) {
         apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Buscando..."));
                
               $ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://www.proxy-list.download/api/v1/get?type=socks5&anon=elite&country=US");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$proxy = curl_exec($ch);

curl_close ($ch);

$va = explode('
', $proxy);

$i = 0;
foreach($va as $line){

    if(++$i > 30) break;
    $unidade = $line;
    $titulos = $titulos."\n".$unidade;
        
} 

if($va[0] == "") {
       
apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else if(strlen($va[1]) > 30){

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5

PROXY NÃO ENCONTRADO!*

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    ))); 

} else {

apiRequest("editMessageText", array('chat_id' => $chat_id, 'message_id' => $message_id, "parse_mode" => "Markdown", "text" => "🇺🇸  *PROXY SOCKS5*
`$titulos`

*BY:* Cabo Robot",
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));     

      }
      } else {
  
      apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id, 'text' => "Você não tem permissão!"));

      }
  }
  apiRequest("answerCallbackQuery", array('callback_query_id' => $callback_id));
}


function processMessage($message) {
	// process incoming message 
	$BOT_TOKEN = "5193842214:AAEIM8_1qI3UwkyyvClqK7Sb0BZnSr91lGw";
    $message_id = $message['message_id'];   
    $chat_id = $message['chat']['id'];
    $type = $message['chat']['type'];
    $titulo = $message['chat']['title'];
    $user_id = $message['from']['id'];
    $first_name = $message['from']['first_name'];
	$last_name = $message['from']['last_name'];
    $username = $message['from']['username'];
    $is_bot = $message['new_chat_participant']['is_bot'];
    $member_name = $message['new_chat_participant']['first_name'];
	$member_last_name = $message['new_chat_participant']['last_name'];
    $member_user = $message['new_chat_participant']['username'];
    $reply_to_message_user_id = $message['reply_to_message']['from']['id'];
    $reply_to_message_name = $message['reply_to_message']['from']['first_name'];
    $reply_to_message_last_name = $message['reply_to_message']['from']['last_name'];
    $reply_to_message_user = $message['reply_to_message']['from']['username'];
    $reply_to_message_id = $message['reply_to_message']['message_id'];
    $forward_from_id = $message['reply_to_message']['forward_from']['id'];
    $forward_from_name = $message['reply_to_message']['forward_from']['first_name'];
    $forward_from_last_name = $message['reply_to_message']['forward_from']['last_name'];
    $forward_from_user = $message['reply_to_message']['forward_from']['username'];       
    $adm = "1484706029";
    $adm2 = "902983459";

    date_default_timezone_set('America/Recife');
    $diasemana = array('Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sabado');
    $data = date('Y/m/d');
    $diasemana_numero = date('w', strtotime($data));
    $dataCerta = date('d/m/Y');
    $hora = date('H:i:s');
    $minuto = date('i');
    $segundo = date('s');

    if (isset($member_name) && $is_bot != '1') {
		if ($member_user !== 'VexedTutoriaisbot') {
			$falas = array('Olá', 'Opa', 'Salve, salve', 'Fala aí', );
			$keys = array_keys($falas);
			shuffle($keys);
			$fala = array_rand($keys);
			
            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*'.$falas[$fala]. ',* ' .$member_name .'! *Seja bem-vindo(a) ao grupo *'. $titulo .'*, sinta-se à vontade.

Conheça abaixo, todos os meus comandos:*', "reply_to_message_id" => $message_id,
		    'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                                 
                                              //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )                                                     
                                            )
                                    )));

         } else if($member_user == 'VexedTutoriaisbot') {
			                  
           $getAdmins = apiRequest('getChatAdministrators', array('chat_id' => $chat_id));
           $json = json_encode($getAdmins, true);
 
            $listAdmins = explode('username":"', $json);
            for ($i=1; $i < count($listAdmins); $i++) { 
                $admins = explode('"', $listAdmins[$i]);
                $adms = $adms." ".$admins[0];
            }
                                         
            if(stripos($adms, "VexedTutoriaisbot")) { 
                                                   
                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*Obrigado por me adicionar como um Administrador no seu grupo!

Conheça a baixo, todos os meus comandos:*', "reply_to_message_id" => $message_id,
		       'reply_markup' => array('inline_keyboard' => array(                                                                                                         
                                              //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )
                                            )
                                    )));
                                       
            }else{
                                                               
                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*✅ OK! Você me adicionou no grupo!

Só tem mais uma coisa...
Para utilizar todas as minhas ferramentas você precisa me colocar como administrador 😉*',
		    'reply_markup' => array('inline_keyboard' => array(                                                      
                                                      //linha 1
                                                     array(                                               
                                                         array('text'=>'Ok, já te coloquei como ADM',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'adm']))                            
                                                      )
                                            )
                                    )));
                
            }
        }
    }
	

    if (isset($message['text'])) {
        $text = $message['text']; 
		$member_name = $message['from']['first_name'];
	

$verifica = substr(strtoupper($text), 0,4) == '/CEP';

        if($verifica){
            $login = substr($text, 5);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/cep.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*CEP Checker* - _Consulta de CEP, obtém informações sobre os logradouros (como nome de rua, avenida, alameda, beco, travessa, praça etc), nome de bairro, cidade e estado onde ele está localizado.

Formato:_
70040010
_ou_
70040-010

`/cep 70040010`",
   'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }else{
                   
                  
                  apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*CEP Checker* - _Consulta de CEP, obtém informações sobre os logradouros (como nome de rua, avenida, alameda, beco, travessa, praça etc), nome de bairro, cidade e estado onde ele está localizado.

Formato:_
70040010
_ou_
70040-010

`/cep 70040010`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }
            }
        }


$verifica = substr(strtoupper($text), 0,5) == '/CPF1';
    
        if($verifica){
            $login = substr($text, 6);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/cpf.php");
                                                             
            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*CPF Checker* - _Consulta simples de CPF, obtém dados do portador.

Formato:_
27867260854
_ou_
278.672.608-54

`/cpf1 27867260854`',
   'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }else{
                   
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*CPF Checker* - _Consulta simples de CPF, obtém dados do portador.

Formato:_
27867260854
_ou_
278.672.608-54

`/cpf1 27867260854`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }
            }
        }
        
        
$verifica = substr(strtoupper($text), 0,5) == '/CPF2';
    
        if($verifica){
            $login = substr($text, 6);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/cpf2.php");
                                                             
            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*CPF Checker* - _Consulta completa de CPF, obtém dados do portador.

Formato:_
27867260854
_ou_
278.672.608-54

`/cpf2 27867260854`',
   'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }else{
                   
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*CPF Checker* - _Consulta completa de CPF, obtém dados do portador.

Formato:_
27867260854
_ou_
278.672.608-54

`/cpf2 27867260854`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }
            }
        }
        
              
$verifica = substr(strtoupper($text), 0,9) == '/PARENTES';
    
        if($verifica){
            $login = substr($text, 10);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/parentes.php");
                                                             
            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*PARENTES Checker* - _Consulta de PARENTES, obtém informações sobre os parentes do portador do número de CPF.

Formato:_
27867260854
_ou_
278.672.608-54

`/parentes 27867260854`',
   'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }else{
                   
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*PARENTES Checker* - _Consulta de PARENTES, obtém informações sobre os parentes do portador do número de CPF.

Formato:_
27867260854
_ou_
278.672.608-54

`/parentes 27867260854`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }
            }
        }
        
        
$verifica = substr(strtoupper($text), 0,9) == '/VIZINHOS';
    
        if($verifica){
            $login = substr($text, 10);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/vizinhos.php");
                                                             
            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*VIZINHOS Checker* - _Consulta de VIZINHOS, obtém os nomes dos vizinhos do portador do número de CPF.

Formato:_
27867260854
_ou_
278.672.608-54

`/vizinhos 27867260854`',
   'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }else{
                   
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*VIZINHOS Checker* - _Consulta de VIZINHOS, obtém os nomes dos vizinhos do portador do número de CPF.

Formato:_
27867260854
_ou_
278.672.608-54

`/vizinhos 27867260854`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }
            }
        }
              
              
$verifica = substr(strtoupper($text), 0,5) == '/CNPJ';

        if($verifica){
            $login = substr($text, 6);
            if($login){
               $split = explode(" ", $login);
               $comando = $split[0];

               include("botcon/cnpj.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*CNPJ Checker* - _Consulta completa de CNPJ, obtém todos os dados da empresa e nome do(s) proprietário(s) e sócio(s).

Formato:_
27865757000102
_ou_
27.865.757/0001-02

`/cnpj 27865757000102`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*CNPJ Checker* - _Consulta completa de CNPJ, obtém todos os dados da empresa e nome do(s) proprietário(s) e sócio(s).

Formato:_
27865757000102
_ou_
27.865.757/0001-02

`/cnpj 27865757000102`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }


$verifica = substr(strtoupper($text), 0,5) == '/NOME';

        if($verifica){
            $login = substr($text, 6);
            if($login){            
               $comando = $login;

               include("botcon/nome.php");

            }else{
                
                if($type == 'private') {
                                                 
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*NOME Checker* - _Consulta simples de nome, obtém o número do CPF do portador.

Formato:_
TANIA MARA MOYSES
_ou_
Tania Mara Moyses

`/nome TANIA MARA MOYSES`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                              
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*NOME Checker* - _Consulta simples de nome, obtém o número do CPF do portador.

Formato:_
TANIA MARA MOYSES
_ou_
Tania Mara Moyses

`/nome TANIA MARA MOYSES`", "reply_to_message_id" => $message_id,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                              
                                                      )
                                                          
                                            )
                                    )));
                                  
                }
            }
        }


$verifica = substr(strtoupper($text), 0,6) == '/PLACA';

        if($verifica){
            $login = substr($text, 7);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/placa.php");

            }else{
                
                if($type == 'private') {
                                                 
                     apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*PLACA Checker* - _Consulta completa de Placa, obtém informações sobre o veículo.

Formato:_
OGT0458

`/placa OGT0458`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                              
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*PLACA Checker* - _Consulta completa de Placa, obtém informações sobre o veículo.

Formato:_
OGT0458

`/placa OGT0458`", "reply_to_message_id" => $message_id,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                              
                                                      )
                                                          
                                            )
                                    )));
                
                }
            }
        }


$verifica = substr(strtoupper($text), 0,4) == '/TEL';

        if($verifica){
            $login = substr($text, 5);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/telefone.php");

            }else{
                
                if($type == 'private') {
                                                 
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*TELEFONE Checker* - _Consulta simples de Número de Telefone, obtém os dados do dono do Telefone.

Formato:_
51995379721

`/tel 51995379721`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                              
                                                      )
                                                          
                                            )
                                    )));

                }else{

                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*TELEFONE Checker* - _Consulta simples de Número de Telefone, obtém os dados do dono do Telefone.

Formato:_
51995379721

`/tel 51995379721`", "reply_to_message_id" => $message_id,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                             
                                                      )
                                                          
                                            )
                                    )));

               }
           }
        }
        
        
$verifica = substr(strtoupper($text), 0,6) == '/EMAIL';

        if($verifica){
            $login = substr($text, 7);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/email.php");

            }else{
                
                if($type == 'private') {
                                                 
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*EMAIL Checker* - _Consulta simples de Email, obtém os dados do dono do Email.

Formato:_
andreia@yahoo.com

`/email andreia@yahoo.com`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                              
                                                      )
                                                          
                                            )
                                    )));

                }else{

                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*EMAIL Checker* - _Consulta simples de Email, obtém os dados do dono do Email.

Formato:_
andreia@yahoo.com

`/email andreia@yahoo.com`", "reply_to_message_id" => $message_id,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'??  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                             
                                                      )
                                                          
                                            )
                                    )));

               }
           }
        }


$verifica = substr(strtoupper($text), 0,8) == '/COMBATE';

        if($verifica){
            $login = substr($text, 9);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botchk/combate.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Combate Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/combate cassio@doyle.com.br|colorad0`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Combate Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/combate cassio@doyle.com.br|colorad0`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }


$verifica = substr(strtoupper($text), 0,9) == '/GLOBOSAT';

        if($verifica){
            $login = substr($text, 10);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botchk/globosat.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker GloboSat Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/globosat cassio@doyle.com.br|colorad0`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker GloboSat Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/globosat cassio@doyle.com.br|colorad0`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }
        
        
$verifica = substr(strtoupper($text), 0,9) == '/PREMIERE';

        if($verifica){
            $login = substr($text, 10);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botchk/premiere.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Premiere Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/premiere cassio@doyle.com.br|colorad0`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Premiere Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/premiere cassio@doyle.com.br|colorad0`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }


$verifica = substr(strtoupper($text), 0,9) == '/TELECINE';

        if($verifica){
            $login = substr($text, 10);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botchk/telecine.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Telecine Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/telecine cassio@doyle.com.br|colorad0`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Telecine Play.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/telecine cassio@doyle.com.br|colorad0`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }
        
        
$verifica = substr(strtoupper($text), 0,8) == '/SEXYHOT';

        if($verifica){
            $login = substr($text, 9);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botchk/sexyhot.php");

            }else{
                
                if($type == 'private') {
	                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Sexy Hot.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/sexyhot cassio@doyle.com.br|colorad0`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                                       
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker Sexy Hot.* _Para utilizar o checker, você precisa digitar o respectivo comando seguido de email e senha. 

Formato:_ email|senha

`/sexyhot cassio@doyle.com.br|colorad0`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }
        
        
$verifica = substr(strtoupper($text), 0,6) == '/CHKGG';

        if($verifica){
            $login = substr($text, 7);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botchk/chkgg.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker GG* - _Testador de Cartões de Crédito ou Débito gerados, testa a validade do cartão e obtém os detalhes do emissor (como onde ele está localizado) e os detalhes do cartão (como tipo, a bandeira e a categoria).

Bandeiras Suportadas:_
MASTERCARD e VISA

_Formato:_
4984069234151378|02|2022|377

`/chkgg 4984069234151378|02|2022|377`",
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Checker GG* - _Testador de Cartões de Crédito ou Débito gerados, testa a validade do cartão e obtém os detalhes do emissor (como onde ele está localizado) e os detalhes do cartão (como tipo, a bandeira e a categoria).

Bandeiras Suportadas:_
MASTERCARD e VISA

_Formato:_
4984069234151378|02|2022|377

`/chkgg 4984069234151378|02|2022|377`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }


       if(strpos($text, "/planos") !== false ){
            
           include("botcon/planos2.php");

       }else if(strpos($text, "/gerarcontas") !== false ){
            
           include("botger/contas/banco.php");

       }else if(strpos($text, "/gerardados") !== false ){
            
           include("botger/dados/genero.php");

       }else if(strpos($text, "/empresas") !== false ){
            
           include("botger/empresa/estadoemp.php");

       }else if(strpos($text, "/gerarcpf") === 0) {      

             if($type == 'private') {
              
              apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*Escolha a quantidade:*',    
		     'reply_markup' => array('inline_keyboard' => array(
                                                         //linha 1
                                                         array(
                                                             array('text'=>' 10 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen10'])), //botão 1                                                                                                                  
                                                             array('text'=>' 30 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen30'])), //botão 2
                                                             array('text'=>' 50 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen50'])) //botão 3                                                                                                                                                                   
                                                          ),
                                                          //linha 2
                                                         array(
                                                             array('text'=>'🔙  Voltar  🔙',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'menu']))//botão com callback                                                   
                                                          ),
                                                           //linha 3
                                                         array(
                                                             array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                                                                      
                                                         )
                                            )
                                    )));
                       
             }else{
                  
                  apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*Escolha a quantidade:*', 'reply_to_message_id' => $message_id,    
		          'reply_markup' => array('inline_keyboard' => array(
                                                         //linha 1
                                                         array(
                                                             array('text'=>' 10 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen10'])), //botão 1                                                                                                                  
                                                             array('text'=>' 30 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen30'])), //botão 2
                                                             array('text'=>' 50 ',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'cpfgen50'])) //botão 3                                                                                                                                                                   
                                                          ),
                                                          //linha 2
                                                         array(
                                                             array('text'=>'🔙  Voltar  🔙',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'menu']))//botão com callback                                                   
                                                          ),
                                                           //linha 3
                                                         array(
                                                             array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                                                                      
                                                         )
                                            )
                                    )));
                       
             }
         }


$verifica = substr(strtoupper($text), 0,4) == '/ADC';

        if($verifica){
            $login = substr($text, 5);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];
               $hora_marcada = $split[1];

               if($hora_marcada == ""){

                   $hora_marcada = $hora;

               }

               $id_data = explode("|", $comando);
          
               $dt = explode("/", $id_data[1]);
               $dia = $dt[0];
               $mes = $dt[1];
               $ano = $dt[2];

               if ($user_id == $adm || $user_id == $adm2) {
                   
                   $array_usuarios = file("botcon/usuarios.txt");
                   $total_usuarios_registrados = count($array_usuarios);
                   $adc_id = explode("|" , $comando);

                   $continuar = false;
                   for($i=0;$i<count($array_usuarios);$i++){
                       $explode = explode("|" , $array_usuarios[$i]);
                       if($adc_id[0] == $explode[0]){         
                           $dt_lista = $explode[1];
                           $continuar = true;
                        }
                    }

                    $dt_hora = explode(" ", $dt_lista);

                    $converte_dt = explode("/", $dt_hora[0]);
                    $c_ano = $converte_dt[0];
                    $c_mes = $converte_dt[1];
                    $c_dia = $converte_dt[2];

                    if($continuar){
                       
                       apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "🚫 *O usuário já está na lista VIP*

━━━━━━━━━━━━━━━━━
ID de Usuário  |  Data de Vencimento
━━━━━━━━━━━━━━━━━
ID:".$adc_id[0]."|".$c_dia."/".$c_mes."/".$c_ano." ".$dt_hora[1],
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                   } else {
	                   if($file = fopen("botcon/usuarios.txt","a+")) {
                           fputs($file, $id_data[0]."|".$ano."/".$mes."/".$dia." ".$hora_marcada."\n");                     
                           
                           apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "✅ *Usuário incluido na lista VIP*

━━━━━━━━━━━━━━━━━
ID de Usuário  |  Data de Vencimento
━━━━━━━━━━━━━━━━━
ID:".$comando." ".$hora_marcada,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
 
                      }
                  }
               }
           }
        }


$verifica = substr(strtoupper($text), 0,3) == '/RM';

        if($verifica){
            $login = substr($text, 4);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               $id_creditos = explode("|", $comando);

               $id = $id_creditos[0];
               $creditos = $id_creditos[1];

               if ($user_id == $adm || $user_id == $adm2) {

                   $array_usuarios = file("botcon/usuarios.txt");
                   $total_usuarios_registrados = count($array_usuarios);
                   
                   $continuar = false;
                   for($i=0;$i<count($array_usuarios);$i++){
                       $explode = explode("|" , $array_usuarios[$i]);
                       if($id == $explode[0]){         
                           $continuar = true;
                        }
                    }

                   if(!$continuar){
                       
                       apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "🚫 *O usuário não está na lista VIP*

━━━━━━━━━━━━━━━━━
ID de Usuário  |  Data da Pesquisa
━━━━━━━━━━━━━━━━━
ID:".$id."|".$dataCerta." ".$hora,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                   } else {

                  $arquivo = file_get_contents('botcon/usuarios.txt');
                  $linhas = explode("\n", $arquivo);
                  $palavra = $id;
                  foreach($linhas as $linha => $valor) {
                      $posicao = preg_match("/\b$palavra\b/i", $valor);                      
                      if($posicao) {
                          unset($linhas[$linha]);
                          
                          apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "✅ *Usuário excluido da lista VIP*

━━━━━━━━━━━━━━━━━
ID de Usuário  |  Data da Exclusão
━━━━━━━━━━━━━━━━━
ID:".$id."|".$dataCerta." ".$hora,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                  
                      }
                  }
                  $arquivo = implode("\n", $linhas);
                  file_put_contents('botcon/usuarios.txt', $arquivo);                    
                
                  }
               }
           }
        }


$verifica = substr(strtoupper($text), 0,9) == '/GRUPOVIP';

        if($verifica){
            $login = substr($text, 10);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];
               $hora_marcada = $split[1];

               if($hora_marcada == ""){

                   $hora_marcada = $hora;

               }

               $id_data = explode("|", $comando);
          
               $dt = explode("/", $id_data[1]);
               $dia = $dt[0];
               $mes = $dt[1];
               $ano = $dt[2];

               if($user_id == $adm || $user_id == $adm2) {
                   
                   $array_grupos = file("botcon/grupos.txt");
                   $total_grupos_registrados = count($array_grupos);
                   $adc_id = explode("|" , $comando);
                   $adc_id[0] = str_replace("-", "", $adc_id[0]);

                   $continuar = false;
                   for($i=0;$i<count($array_grupos);$i++){
                       $explode = explode("|" , $array_grupos[$i]);
                       if($adc_id[0] == $explode[0]){         
                           $dt_lista = $explode[1];
                           $continuar = true;
                        }
                    }

                    $dt_hora = explode(" ", $dt_lista);

                    $converte_dt = explode("/", $dt_hora[0]);
                    $c_ano = $converte_dt[0];
                    $c_mes = $converte_dt[1];
                    $c_dia = $converte_dt[2];

                    if($continuar){
                       
                       apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "🚫 *O grupo já está na lista VIP*

━━━━━━━━━━━━━━━━━
Chat-id do Grupo|Data de Vencimento
━━━━━━━━━━━━━━━━━
Chat-id: -".$adc_id[0]."|".$c_dia."/".$c_mes."/".$c_ano." ".$dt_hora[1],
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                   } else {
	                   if($file = fopen("botcon/grupos.txt","a+")) {
                           fputs($file, "\n".$adc_id[0]."|".$ano."/".$mes."/".$dia." ".$hora_marcada);                     
                           
                           apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "✅ *Grupo incluido na lista VIP*

━━━━━━━━━━━━━━━━━
Chat-id do Grupo|Data de Vencimento
━━━━━━━━━━━━━━━━━
Chat-id: -".$adc_id[0]."|".$id_data[1]." ".$hora_marcada,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
 
                      }
                  }
               }
           }
        }


$verifica = substr(strtoupper($text), 0,8) == '/EXCLUIR';

        if($verifica){
            $login = substr($text, 9);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];
            
               if($user_id == $adm || $user_id == $adm2) {

                   $array_grupos = file("botcon/grupos.txt");
                   $total_grupos_registrados = count($array_grupos);                  
                   $comando = str_replace("-", "", $comando);
                  
                   $continuar = false;
                   for($i=0;$i<count($array_grupos);$i++){
                       $explode = explode("|" , $array_grupos[$i]);
                       if($comando == $explode[0]){                                    
                           $continuar = true;
                        }
                    }

                   if(!$continuar){
                       
                       apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "🚫 *O grupo não está na lista VIP*

━━━━━━━━━━━━━━━━━
Chat-id do Grupo  |  Data da Pesquisa
━━━━━━━━━━━━━━━━━
Chat-id: -".$comando."|".$dataCerta." ".$hora,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                   } else {

                  $arquivo = file_get_contents('botcon/grupos.txt');
                  $linhas = explode("\n", $arquivo);
                  $palavra = $comando;
                  foreach($linhas as $linha => $valor) {
                      $posicao = preg_match("/\b$palavra\b/i", $valor);                      
                      if($posicao) {
                          unset($linhas[$linha]);
                          
                          apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "✅ *Grupo excluido da lista VIP*

━━━━━━━━━━━━━━━━━
Chat-id do Grupo  |  Data da Exclusão
━━━━━━━━━━━━━━━━━
Chat-id: -".$comando."|".$dataCerta." ".$hora,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                  
                      }
                  }
                  $arquivo = implode("\n", $linhas);
                  file_put_contents('botcon/grupos.txt', $arquivo);                    
                
                  }
               }
           }
        }
        

       if(strpos($text, "/lista") === 0) {
                        
            if ($user_id == $adm) {
            
                $array_usuarios = file("botcon/usuarios.txt");
                $total_usuarios_registrados = count($array_usuarios);
                
                $lista = file_get_contents('botcon/usuarios.txt');
                                                               
                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "📋 *Lista de Usuários VIP*
━━━━━━━━━━━━━━━━━
ID de Usuário  |  Data de Vencimento
━━━━━━━━━━━━━━━━━
$lista
━━━━━━━━━━━━━━━━━
Total de Usuários: ".$total_usuarios_registrados,
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

            }
        }

        
$verifica = substr(strtoupper($text), 0,4) == '/BIN';

        if($verifica){
            $login = substr($text, 5);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/bin.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*BIN Checker* - _Consulta de BIN, obtém os detalhes do emissor (como qual banco ou instituição financeira emitiu o cartão e onde ele está localizado), o tipo, a bandeira e a categoria do cartão.

Formato:_
498408

`/bin 498408`',
     'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

                }else{
                    
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*BIN Checker* - _Consulta de BIN, obtém os detalhes do emissor (como qual banco ou instituição financeira emitiu o cartão e onde ele está localizado), o tipo, a bandeira e a categoria do cartão.

Formato:_
498408

`/bin 498408`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));
                    
               }   
           }
        }


$verifica = substr(strtoupper($text), 0,3) == '/IP';

        if($verifica){
            $login = substr($text, 4);
            if($login){
               $split = explode(" ", $login);               
               $comando = $split[0];

               include("botcon/ip.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*IP Checker* - _Consulta o número de IP, obtém dados do IP, como qual é o provedor, ip reverso, país, estado, cidade e as coordenadas de onde ele está localizado.

Formato:_
204.152.203.157

`/ip 204.152.203.157`",
   'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }else{
                   
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*IP Checker* - _Consulta o número de IP, obtém dados do IP, como qual é o provedor, ip reverso, país, estado, cidade e as coordenadas de onde ele está localizado.

Formato:_
204.152.203.157

`/ip 204.152.203.157`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }
            }
        }
 
        
$verifica = substr(strtoupper($text), 0,5) == '/SITE';

        if($verifica){
            $login = substr($text, 6);
            if($login){
               $split = explode(" ", $login);
               $comando = $split[0];

               include("botcon/site.php");

            }else{
                
                if($type == 'private') {
	
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*SITE Checker* - _Consulta a url de um SITE, obtém dados do site, como qual é o ip, ip reverso, provedor, país, estado, cidade e as coordenadas de onde ele está localizado.

Formato:_
http://google.com
_ou_
google.com

`/site google.com`",
   'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }else{
                   
                   
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*SITE Checker* - _Consulta a url de um SITE, obtém dados do site, como qual é o ip, ip reverso, provedor, país, estado, cidade e as coordenadas de onde ele está localizado.

Formato:_
http://google.com
_ou_
google.com

`/site google.com`", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>'apagar')//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));

               }
            }
        }


        if (strpos($text, "/menu") === 0) {          
            if($type == 'private') {
            
            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '✅ *MENU DE COMANDOS*

*Escolha uma das opções a baixo e clique no botão correspondente.*',   
		    'reply_markup' => array('inline_keyboard' => array(                                                                                                            
                                //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )
                                            )
                                    )));
            }else{
             
             apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '✅ *MENU DE COMANDOS*

*Escolha uma das opções a baixo e clique no botão correspondente.*', 'reply_to_message_id' => $message_id,
		    'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                               
                               //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )
                                            )
                                    )));
            
                      
             }
         }

        if(strpos($text, "/mute") === 0 && $type !== 'private') {

            $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
            $json = json_encode($getAdmins, true);
                                                                                                                                      
            function getStr($string,$start,$end){
	            $str = explode($start,$string);
	            $str = explode($end,$str[1]);
	            return $str[0];
            }
                   
           $status = getStr($json,'status":"','"');
           $permissao = getStr($json,'can_restrict_members":',',');                             
 
           if($status == 'creator' || $permissao == 'true') {     

                $split = explode(" ", $text);
                if($split[1]) {
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $split[1]));
                }else{
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));
                }

                $getmemberlist = json_encode($getmember, JSON_UNESCAPED_UNICODE);
                                                                                                                                                      
                $member_id = getStr($getmemberlist,'id":',',');                    
                $member_name = getStr($getmemberlist,'first_name":"','"');
                $member_username = getStr($getmemberlist,'username":"','"');
                $member_status = getStr($getmemberlist,'status":"','"');

                if($split[1] && $member_id) {
                    if ($member_status == 'creator' || $member_status == 'administrator') {
                        apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário faz parte da Staff do grupo
Você não pode silenciar.*", "reply_to_message_id" => $message_id));
                    }else{
                        apiRequest('restrictChatMember', array('chat_id' => $chat_id, 'user_id' => $member_id));
                        if($member_username) {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$member_username](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: SILENCIADO! 🔇*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Dessilenciar  ✅',"callback_data"=>"unmute $member_id $member_name $member_username")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

                        }else{
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$member_name](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: SILENCIADO! 🔇*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Dessilenciar  ✅',"callback_data"=>"unmute $member_id $member_name")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

                        }
                    }
                }else if(!$split[1] && $member_id) {                      
                    if(!$reply_to_message_user_id){
                    	apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "⚠️ Você precisa marcar alguma mensagem, para que eu possa saber, qual membro devo silenciar.", "reply_to_message_id" => $message_id));                                    
                    } else {
                        if ($member_status == 'creator' || $member_status == 'administrator') {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário faz parte da Staff do grupo
Você não pode silenciar.*", "reply_to_message_id" => $message_id));                          
                        }else{
                            apiRequest('restrictChatMember', array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));                    
                            if($reply_to_message_user) {
                                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$reply_to_message_user](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: SILENCIADO! 🔇*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Dessilenciar  ✅',"callback_data"=>"unmute $reply_to_message_user_id $reply_to_message_name $reply_to_message_user")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

                            }else{
                        	    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$reply_to_message_name](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: SILENCIADO! 🔇*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Dessilenciar  ✅',"callback_data"=>"unmute $reply_to_message_user_id $reply_to_message_name")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));

                            }
                        }
                    }
                }
            }


        }else if(strpos($text, "/unmute") === 0 && $type !== 'private') {

            $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
            $json = json_encode($getAdmins, true);
                                                                                                                                      
            function getStr($string,$start,$end){
	            $str = explode($start,$string);
	            $str = explode($end,$str[1]);
	            return $str[0];
            }
                   
           $status = getStr($json,'status":"','"');
           $permissao = getStr($json,'can_restrict_members":',',');                             
 
           if($status == 'creator' || $permissao == 'true') {     

                $split = explode(" ", $text);
                if($split[1]) {
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $split[1]));
                }else{
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));
                }

                $getmemberlist = json_encode($getmember, JSON_UNESCAPED_UNICODE);
                                                                                                                                                    
                $member_id = getStr($getmemberlist,'id":',',');                    
                $member_name = getStr($getmemberlist,'first_name":"','"');
                $member_username = getStr($getmemberlist,'username":"','"');
                $member_status = getStr($getmemberlist,'status":"','"');

                if($split[1] && $member_id) {
                    if ($member_status != 'restricted') {
                        apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário não está silenciado.*", "reply_to_message_id" => $message_id));
                    }else{
                        apiRequest('restrictChatMember', array('chat_id' => $chat_id, 'user_id' => $member_id, 'can_send_messages' => '1', 'can_send_media_messages' => '1', 'can_send_other_messages' => '1', 'can_add_web_page_previews' => '1'));
                        if($member_username) {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$member_username](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: DESSILENCIADO! ✅*", "reply_to_message_id" => $message_id)); 
 
                        }else{
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$member_name](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: DESSILENCIADO! ✅*", "reply_to_message_id" => $message_id)); 

                        }
                    }
                }else if(!$split[1] && $member_id) {                  
                    if(!$reply_to_message_user_id){
                    	apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "⚠️ Você precisa marcar alguma mensagem, para que eu possa saber, qual membro devo dessilenciar.", "reply_to_message_id" => $message_id));                                    
                    } else {
                        if ($member_status != 'restricted') {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário não está silenciado.*", "reply_to_message_id" => $message_id));
                        }else{
                            apiRequest('restrictChatMember', array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id, 'can_send_messages' => '1', 'can_send_media_messages' => '1', 'can_send_other_messages' => '1', 'can_add_web_page_previews' => '1'));                                    
                            if($reply_to_message_user) {
                                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$reply_to_message_user](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: DESSILENCIADO! ✅*", "reply_to_message_id" => $message_id)); 
 
                           }else{
                       	    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$reply_to_message_name](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: DESSILENCIADO! ✅*", "reply_to_message_id" => $message_id)); 

                           }
                       }
                   }
                }
            }


        }else if(strpos($text, "/ban") === 0 && $type !== 'private') {

            $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
            $json = json_encode($getAdmins, true);
                                                                                                                                      
            function getStr($string,$start,$end){
	            $str = explode($start,$string);
	            $str = explode($end,$str[1]);
	            return $str[0];
            }
                   
           $status = getStr($json,'status":"','"');
           $permissao = getStr($json,'can_restrict_members":',',');                             
 
           if($status == 'creator' || $permissao == 'true') {     

                $split = explode(" ", $text);
                if($split[1]) {
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $split[1]));
                }else{
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));
                }

                $getmemberlist = json_encode($getmember, JSON_UNESCAPED_UNICODE);
                                                                                                                                                      
                $member_id = getStr($getmemberlist,'id":',',');                    
                $member_name = getStr($getmemberlist,'first_name":"','"');
                $member_username = getStr($getmemberlist,'username":"','"');
                $member_status = getStr($getmemberlist,'status":"','"');
                
                if($split[1] && $member_id) {                                                          
                    if ($member_status == 'creator' || $member_status == 'administrator') {
                        apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário faz parte da Staff do grupo
Você não pode banir.*", "reply_to_message_id" => $message_id));
                    }else{
                        apiRequest('kickChatMember', array('chat_id' => $chat_id, 'user_id' => $member_id));
                        if($member_username) {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$member_username](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: BANIDO! 🚷*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Desbanir  ✅',"callback_data"=>"unban $member_id $member_name $member_username")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));
                      
                        }else{
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$member_name](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: BANIDO! 🚷*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Desbanir  ✅',"callback_data"=>"unban $member_id $member_name")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));
 
                        }
                    }
                }else if(!$split[1] && $member_id) {                   
                    if(!$reply_to_message_user_id){
                    	apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "⚠️ Você precisa marcar alguma mensagem, para que eu possa saber, qual membro devo banir.", "reply_to_message_id" => $message_id));                                    
                    } else {
                        if ($member_status == 'creator' || $member_status == 'administrator') {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário faz parte da Staff do grupo
Você não pode banir.*", "reply_to_message_id" => $message_id));                          
                        }else{
                             apiRequest('kickChatMember', array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));
                             //apiRequest("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $reply_to_message_message_id));                
                             if($reply_to_message_user) {
                                 apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$reply_to_message_user](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: BANIDO! 🚷*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Desbanir  ✅',"callback_data"=>"unban $reply_to_message_user_id $reply_to_message_name $reply_to_message_user")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));
                      
                            }else{
                        	    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$reply_to_message_name](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: BANIDO! 🚷*", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'✅  Desbanir  ✅',"callback_data"=>"unban $reply_to_message_user_id $reply_to_message_name")//botão com callback                                                                                              
                                                      )
                                                                                                                
                                            )
                                    )));
 
                       	 }
                        }
                    }
                }
            }


        }else if(strpos($text, "/unban") === 0 && $type !== 'private') {

            $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
            $json = json_encode($getAdmins, true);
                                                                                                                                      
            function getStr($string,$start,$end){
	            $str = explode($start,$string);
	            $str = explode($end,$str[1]);
	            return $str[0];
            }
                   
           $status = getStr($json,'status":"','"');
           $permissao = getStr($json,'can_restrict_members":',',');                             
 
           if($status == 'creator' || $permissao == 'true') {     

                $split = explode(" ", $text);
                if($split[1]) {
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $split[1]));
                }else{
                    $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));
                }

                $getmemberlist = json_encode($getmember, JSON_UNESCAPED_UNICODE);
                                                                                                                                                      
                $member_id = getStr($getmemberlist,'id":',',');                    
                $member_name = getStr($getmemberlist,'first_name":"','"');
                $member_username = getStr($getmemberlist,'username":"','"');
                $member_status = getStr($getmemberlist,'status":"','"');

                if($split[1] && $member_id) {
                    if ($member_status != 'kicked') {
                        apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário não está banido do grupo.*", "reply_to_message_id" => $message_id));
                    }else{
                        apiRequest('unbanChatMember', array('chat_id' => $chat_id, 'user_id' => $member_id));
                        if($member_username) {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$member_username](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: DESBANIDO!* ✅", "reply_to_message_id" => $message_id)); 
 
                        }else{
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$member_name](tg://user?id=$member_id) *[*`".$member_id."`*]*
*Ação: DESBANIDO!* ✅", "reply_to_message_id" => $message_id)); 

                        }
                    }
                }else if(!$split[1] && $member_id) {              
                    if(!$reply_to_message_user_id){
                    	apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "⚠️ Você precisa marcar alguma mensagem, para que eu possa saber, qual membro devo desbanir.", "reply_to_message_id" => $message_id));                                    
                    } else {
                        if ($member_status != 'kicked') {
                            apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*O usuário não está banido do grupo.*", "reply_to_message_id" => $message_id));
                        }else{              
                            apiRequest('unbanChatMember', array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));                                    
                            if($reply_to_message_user) {
                                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[@$reply_to_message_user](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: DESBANIDO!* ✅", "reply_to_message_id" => $message_id)); 
 
                           }else{
                       	    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "[$reply_to_message_name](tg://user?id=$reply_to_message_user_id) *[*`".$reply_to_message_user_id."`*]*
*Ação: DESBANIDO!* ✅", "reply_to_message_id" => $message_id)); 

                           }
                       }
                   }
                }
            }


        }else if(strpos($text, "/pin") === 0 && $type !== 'private') {

            $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
            $json = json_encode($getAdmins, true);
                                                                                                                                      
            function getStr($string,$start,$end){
	            $str = explode($start,$string);
	            $str = explode($end,$str[1]);
	            return $str[0];
            }
                   
           $status = getStr($json,'status":"','"');
           $permissao = getStr($json,'can_pin_messages":',',');                             
 
           if($status == 'creator' || $permissao == 'true') {
                apiRequest('pinChatMessage', array('chat_id' => $chat_id, 'message_id' => $reply_to_message_id));
            }


        }else if(strpos($text, "/unpin") === 0 && $type !== 'private') {

            $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
            $json = json_encode($getAdmins, true);
                                                                                                                                      
            function getStr($string,$start,$end){
	            $str = explode($start,$string);
	            $str = explode($end,$str[1]);
	            return $str[0];
            }
                   
           $status = getStr($json,'status":"','"');
           $permissao = getStr($json,'can_pin_messages":',',');                             
 
           if($status == 'creator' || $permissao == 'true') {
                apiRequest('unpinChatMessage', array('chat_id' => $chat_id));
            }


        }else if(strpos($text, "/origem") === 0) { 

            if($user_id == $adm) {
                
                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*INFORMAÇÕES DO USUÁRIO:

ID: *`".$forward_from_id."`
*Nome: *`".$forward_from_name." ".$forward_from_last_name."`
*Username: *`@".$forward_from_user."`", "reply_to_message_id" => $message_id));                 
            }


        }else if(strpos($text, "/link") === 0) {
 
           if($type !== 'private') {
                $chatLink = apiRequest("exportChatinviteLink", array('chat_id' => $chat_id)); 
                if ($chatLink !== false) {
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $chatLink, 'reply_to_message_id' => $message_id)); 

                } else {

                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*Não encontrei o link do grupo. Provavelmente, eu não sou um Admin!*", 
'reply_to_message_id' => $message_id)); 
    
                }
            }
     
       }else if (strpos($text, "/user_id") !== false) { 
            if($user_id == $adm) {               
                
                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*INFORMAÇÕES DO USUÁRIO:

ID:* `".$reply_to_message_user_id."`
*Nome: *`".$reply_to_message_name." ".$reply_to_message_last_name."`
*Username: *`@".$reply_to_message_user."`", "reply_to_message_id" => $message_id)); 
            }
        }

       switch ($text) {    
       case '/info':

           if($type !== 'private') { 
               
               if(!$reply_to_message_user_id){
                   apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*⚠️ Usuário não encontrado!*", "reply_to_message_id" => $message_id)); 
               }else{
                                         
                   $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
                   $json = json_encode($getAdmins, true);
                                                                                                                                      
                   function getStr($string,$start,$end){
	                   $str = explode($start,$string);
	                   $str = explode($end,$str[1]);
	                   return $str[0];
                   }
                   
                   $status = getStr($json,'status":"','"');
 
                   if($status == 'creator' || $status == 'administrator') {
                                                                    
                       $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));
                       $getmemberlist = json_encode($getmember, JSON_UNESCAPED_UNICODE);
                                                                                                                                                             
                       $member_id = getStr($getmemberlist,'id":',',');
                       $member_is_bot = getStr($getmemberlist,'is_bot":',',');
                       $member_name = getStr($getmemberlist,'first_name":"','"');                                              
                       $member_username = getStr($getmemberlist,'username":"','"');
                       $member_status = getStr($getmemberlist,'status":"','"');
                       
                       if($member_is_bot == 'false'){
                           $member_is_bot = 'Não';
                       }else{
                           $member_is_bot = 'Sim';
                       }
                       
                       if($member_status == 'creator'){
                           $member_status = 'Criador';
                       }else if($member_status == 'administrator'){
                           $member_status = 'Administrador';
                       }else if($member_status == 'member'){
                           $member_status = 'Membro';
                       }else if($member_status == 'kicked'){
                           $member_status = 'Banido';
                       }else if($member_status == 'restricted'){
                           $member_status = 'Silenciado';
                       }else{
                           $member_status = 'Não é membro';
                       }
                       
                       if ($member_status != 'Membro') {
                           if ($member_username) {
                               
                               apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$member_id."`
*🤖 Bot:* $member_is_bot
*🧑🏻 Nome: * [$member_name](tg://user?id=$member_id)
*🌐 Username: * [@$member_username](tg://user?id=$member_id)
*👀 Situação:* $member_status", "reply_to_message_id" => $message_id));

                           }else{
                               
                               apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$member_id."`
*🤖 Bot:* $member_is_bot
*🧑🏻 Nome: * [$member_name](tg://user?id=$member_id)
*👀 Situação:* $member_status", "reply_to_message_id" => $message_id));

                           }                           
                       }else{
                           if ($member_username) {
                               
                               apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$member_id."`
*🤖 Bot:* $member_is_bot
*🧑🏻 Nome: * [$member_name](tg://user?id=$member_id)
*🌐 Username: * [@$member_username](tg://user?id=$member_id)
*👀 Situação:* $member_status", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔇 Silenciar ',"callback_data"=>"mute $reply_to_message_user_id $reply_to_message_name $reply_to_message_user"),//botão com callback                                                                                             
                                                         array('text'=>'🚷 Ban ',"callback_data"=>"ban $reply_to_message_user_id $reply_to_message_name $reply_to_message_user")//botão com callback
                                                      )
                                                                                                                
                                            )
                                    )));

                           }else{
                               
                               apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$member_id."`
*🤖 Bot:* $member_is_bot
*🧑🏻 Nome: * [$member_name](tg://user?id=$member_id)
*👀 Situação:* $member_status", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                        
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🔇 Silenciar ',"callback_data"=>"mute $reply_to_message_user_id $reply_to_message_name"),//botão com callback                                                                                              
                                                         array('text'=>'🚷 Ban ',"callback_data"=>"ban $reply_to_message_user_id $reply_to_message_name")//botão com callback
                                                    )
                                                                                                                
                                            )
                                    )));

                           }
                       }
                   }
               }
            }
            break; 
 

       case '/infopvt':

           if($type !== 'private') { 
               
               if(!$reply_to_message_user_id){
                   apiRequest("sendMessage", array('chat_id' => $user_id, "parse_mode" => "Markdown", "text" => "*⚠️ Usuário não encontrado!*")); 
               }else{

                   $getAdmins = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $user_id));
                   $json = json_encode($getAdmins, true);
                                                                                                                                      
                   function getStr($string,$start,$end){
	                   $str = explode($start,$string);
	                   $str = explode($end,$str[1]);
	                   return $str[0];
                   }
                   
                   $status = getStr($json,'status":"','"');                             
 
                   if($status == 'creator' || $status == 'administrator') {               

                       $getmember = apiRequest("getChatMember", array('chat_id' => $chat_id, 'user_id' => $reply_to_message_user_id));
                       $getmemberlist = json_encode($getmember, JSON_UNESCAPED_UNICODE);
                                                                                                                                                            
                       $member_id = getStr($getmemberlist,'id":',',');
                       $member_is_bot = getStr($getmemberlist,'is_bot":',',');
                       $member_name = getStr($getmemberlist,'first_name":"','"');                                              
                       $member_username = getStr($getmemberlist,'username":"','"');
                       $member_status = getStr($getmemberlist,'status":"','"');                       
                       
                       if($member_is_bot == 'false'){
                           $member_is_bot = 'Não';
                       }else{
                           $member_is_bot = 'Sim';
                       }
                       
                       if($member_status == 'creator'){
                           $member_status = 'Criador';
                       }else if($member_status == 'administrator'){
                           $member_status = 'Administrador';
                       }else if($member_status == 'member'){
                           $member_status = 'Membro';
                       }else if($member_status == 'kicked'){
                           $member_status = 'Banido';
                       }else if($member_status == 'restricted'){
                           $member_status = 'Silenciado';
                       }else{
                           $member_status = 'Não é membro';
                       }

                       if ($member_username) {
                           
                           apiRequest("sendMessage", array('chat_id' => $user_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$member_id."`
*🤖 Bot:* $member_is_bot
*🧑🏻 Nome: * [$member_name](tg://user?id=$member_id)
*🌐 Username: * [@$member_username](tg://user?id=$member_id)
*👀 Situação:* $member_status")); 

                       }else{
                           
                           apiRequest("sendMessage", array('chat_id' => $user_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$member_id."`
*🤖 Bot:* $member_is_bot
*🧑🏻 Nome: * [$member_name](tg://user?id=$member_id)
*👀 Situação:* $member_status")); 

                       }
                   }
               }
            }
            break; 
 
 
        case '/id': 

            if($type == 'private') {

                if ($username != "") {
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$user_id."`
*🧑🏻 Nome:* $first_name $last_name
*🌐 Username: *[@$username](tg://user?id=$user_id)", "reply_to_message_id" => $message_id));          
                } else {
                	
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO USUÁRIO:

🆔:* `".$user_id."`
*🧑🏻 Nome:* $first_name $last_name", "reply_to_message_id" => $message_id));          
                }

           } else {

                if ($username != "") {
                    
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO GRUPO:

Chat ID:* `".$chat_id."`
*Titulo:* $titulo

*📁 INFORMAÇÕES DO USUÁRIO:*

*🆔:* `".$user_id."`
*🧑🏻 Nome:* $first_name $last_name
*🌐 Username: *[@$username](tg://user?id=$user_id)", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));    
 
               } else {
                	
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => "*📁 INFORMAÇÕES DO GRUPO:

Chat ID:* `".$chat_id."`
*Titulo:* $titulo

*📁 INFORMAÇÕES DO USUÁRIO:*

*🆔:* `".$user_id."`
*🧑🏻 Nome:* $first_name $last_name", "reply_to_message_id" => $message_id,
'reply_markup' => array('inline_keyboard' => array(                                                                                                                                                    
                                                      //linha 1
                                                     array(
                                                         array('text'=>'🗑  Apagar  🗑',"callback_data"=>serialize(['id'=>$user_id, 'data'=>'apagar']))//botão com callback                                                   
                                                      )
                                                          
                                            )
                                    )));     

                }

           }

           break;
     
       }

                    
        switch ($text) {    
        case '/start':    
             
		   if($type == 'private') {
				
                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*Olá, *'. $message['from']['first_name'].'! 
*Meu nome é Cabo Robot. Adicione-me em um grupo e me coloque como Admin para utilizar todos os meus recursos!

Conheça abaixo, todos os meus comandos:*', 
		'reply_markup' => array('inline_keyboard' => array(
                               //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )
                                            )
                                    )));
                               
		  }else{
				
                apiRequest("sendMessage", array('chat_id' => $chat_id, "parse_mode" => "Markdown", "text" => '*Olá, *'. $message['from']['first_name'].'! *Eu já fui adicionado nesse grupo!

Conheça abaixo, todos os meus comandos:*', 'reply_to_message_id' => $message_id,
		'reply_markup' => array('inline_keyboard' => array(                                                     
                                                     //linha 1
                                                     array(
                                                         array('text'=>'➕ Adicione-me a um grupo ➕','url'=>'https://t.me/VexedTutoriaisbot?startgroup=start')                                            
                                                      ),
                                                     //linha 4
                                                     array(
                                                          array('text'=>'🔆 Comandos ',"callback_data"=>'comandosgrupos'),//botão com callback                                                
                                                      )
                                            )
                                    )));
                                    
		   }
                         
       break;
     
       }    
	}
} 



define('WEBHOOK_URL', 'https://caborobot.herokuapp.com/bot.php');
if (php_sapi_name() == 'cli') {
  // if run from console, set or delete webhook
  apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
  exit;
}
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) {
  exit;
} else if (isset($update["message"])) {
  processMessage($update["message"]);
} else if (isset($update["callback_query"])) {
  processaCallbackQuery($update["callback_query"]);
}


?>