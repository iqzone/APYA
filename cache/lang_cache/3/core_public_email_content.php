<?php

/*******************************************************
NOTE: This is a cache file generated by IP.Board on Tue, 18 Sep 2012 22:32:41 +0000 by Julio César Barrera A.
Do not translate this file as you will lose your translations next time you edit via the ACP
Please translate via the ACP
*******************************************************/



$lang = array( 
'account_created' => "<#NAME#>,

Tu cuenta ha sido creada con éxito en <#BOARD_NAME#>.

Si estamos esperando un formulario de consentimiento de los padres, esto significa que el formulario ha sido recibido y documentado. Tus datos son los siguientes: 

Nombre de usuario: <#NAME#> 
E-mail: <#EMAIL#> 
Contraseña: <#PASSWORD#> 

Tenga en cuenta que nosotros no guardamos una copia de texto plano de su contrseña, y que puedes cambiar tu contraseña en cualquier momento a través de tu panel de control en la web. 

Visita este enlace para unirte a nuestros debates!


<#BOARD_ADDRESS#>",
'admin_newuser' => "Hola, 

has recibido este email porque un nuevo usuario se ha registrado, 

<#MEMBER_NAME#> ha completado su registro el <#DATE#>

Puedes anular la notificación en el Panel de Control de Administración.

Que tengas un buen día!",
'complete_reg' => "¡Bien! 
Un administrador ha aceptado tu solicitud de registro o de cambio en la dirección de correo electrónico <#BOARD_NAME#>. Ahora puedes entrar con los detalles de tu elección y tendrás el acceso pleno a tu cuenta de usuario en <#BOARD_ADDRESS#>",
'digest_forum_daily' => "<#NAME#>, 

Este es tu resumen diario de nuevos temas! 

---------------------------------------------------------------------- 
<#CONTENT#>
---------------------------------------------------------------------- 


Puedes encontrar el foro aquí: <#BOARD_ADDRESS#>?showforum=<#FORUM_ID#> 

Darte de baja:
--------------

Te puedes dar de baja en cualquier momento conectándote en tu Panel de Control y pulsando en \"Gestionar Foros Vistos\" en la ficha de \"Foros\".",
'digest_forum_weekly' => "<#NAME#>, 

Este es el resumen de mensajes de esta semana en el foro  <#NAME#>. ---------------------------------------------------------------------- 
<#CONTENT#> 
---------------------------------------------------------------------- 

El tema se puede encontrar aquí: <#BOARD_ADDRESS#>?showtopic=<#TOPIC_ID#>&view=getnewpost 

Darte de baja:
--------------

Te puedes dar de baja en cualquier momento conectándote en tu Panel de Control y pulsando en \"Gestionar Temas Vistos\" en la ficha de \"Foros\".",
'digest_topic_daily' => "<#NAME#>, 

Este es el resumen de mensajes en el tema \"<#TITLE#>\" de hoy. 

----------------------------------------------------------------------

 <#CONTENT#> 

---------------------------------------------------------------------- 

Puedes ver el tema aquí: <#BOARD_ADDRESS#>?showtopic=<#TOPIC_ID#>&view=getnewpost

 Darte de baja:
 -------------- 

Te puedes dar de baja en cualquier momento conectándote en tu Panel de Control y pulsando en \"Gestionar Temas Vistos\" en la ficha de \"Foros\".",
'digest_topic_weekly' => "<#NAME#>,

Este es tu resumen semanal de nuevos temas! ---------------------------------------------------------------------- 

<#CONTENT#>

 ---------------------------------------------------------------------- 


Puedes encontrar el foro aquí: <#BOARD_ADDRESS#>?showforum=<#FORUM_ID#> 

Darte de baja:
-------------- 
Te puedes dar de baja en cualquier momento conectándote en tu Panel de Control y pulsando en \"Gestionar Foros Vistos\" en la ficha de \"Foros\".",
'email_convo' => "<#NAME#>,

El Adjunto de este mensaje es un fichero HTML que contiene un archivo de una conversación personal:
Título de la conversación: <#TITLE#>
Conversación empezada: <#DATE#>
<#LINK#>",
'email_member' => "<#MEMBER_NAME#>,

<#FROM_NAME#> te ha enviado este mensaje desde <#BOARD_ADDRESS#>.


<#MESSAGE#>

------------------------------------------------------
Por favor, ten en cuenta que <#BOARD_NAME#> no tiene
control sobre el contenido de este mensaje.
------------------------------------------------------
",
'error_log_notification' => "Estimado administrador,

un error se ha generado en tus foros. Se te ha enviado esta notificación basado en la configuración en el Panel de Control del Admin del log de errores. 
Este error se ajusta a los criterios de los errores que has establecido para que se te notifique. 

El código de error es: <#CODE#> 
El mensaje de error es: <#MESSAGE#> 
El usuario que vio este error es: <#VIEWER#> 
La dirección IP de este usuario es: <# IP_ADDRESS#> 

Por favor, accede a tu Admin Panel para utilizar la herramienta Visor de registro de errores para obtener más información.

<#BOARD_ADDRESS#>",
'footer' => "Saludos.

El equipo de <#BOARD_NAME#> 

<#BOARD_ADDRESS#>",
'forward_page' => "<#TO_NAME#> 

<#THE_MESSAGE#>
--------------------------------------------------- 
Por favor, ten en cuenta que <#BOARD_NAME#> no tiene control sobre los contenidos de este mensaje.
--------------------------------------------------- ",
'header' => "encabezado",
'lost_pass' => "<#NAME#> 

Este correo ha sido enviado desde <#BOARD_ADDRESS#>. 

Has recibido este mensaje porque has pedido una recuperación de contraseña en <#BOARD_ADDRESS#>. 

------------------------------------------------
 ¡IMPORTANTE!
 ------------------------------------------------

Si no has pedido un cambio de contraseña, IGNORA y BORRA inmediatamente este email. Sólo continúa si deseas que tu contraseña se resetee. 

------------------------------------------------ 
Instrucciones de Activación a continuación
------------------------------------------------ 
Se requiere que \"valides\" tu cambio de contraseña para asegurarnos que tú has provocado esta acción. Esto te protegerá contra el spam no deseado y el abuso malicioso. 

Simplemente haz clic en el siguiente enlace: <#THE_LINK#> 

(Con algunos programas de correo electrónico puedes tener que copiar y pegar el enlace en tu navegador web). 

------------------------------------------------ 
No funciona ?
 ------------------------------------------------ 

Si no puedes validar tu inscripción pulsando sobre el enlace, por favor, visita esta página:

<#MAN_LINK#>

 Se te pedirá un número de identificación de usuario y tu clave de validación. Estos se muestran a continuación:

Nombre de usuario: <#ID#> 
Clave de Validación: <#CODE#>
Por favor, copia y pega, o escribe los números en los campos correspondientes en el formulario. 

------------------------------------------------ 
No funciona esto ???
------------------------------------------------ 

Si no puedes volver a activar tu cuenta, es posible que la cuenta ha sido suprimida o que estás en el proceso de otra activación, tales como registrar o cambiar tu dirección de correo electrónico registrada. Si este es el caso, entonces por favor completa la activación anterior. Si el error persiste, pónte en contacto con un administrador para corregir el problema. 

Dirección IP del que envía: <#IP_ADDRESS#>  ",
'lost_pass_email_pass' => "<#NAME#>

Este correo ha sido enviado desde <#BOARD_ADDRESS#>. 

Este mensaje de correo electrónico completa tu solicitud de contraseña perdida.

------------------------------------------------ 
TU NUEVA CONTRASEÑA 
------------------------------------------------ 

Tu nombre de usuario es: <#USERNAME#> 
Tu dirección de correo electrónico es: <#EMAIL#> 
Tu nueva contraseña es: <#PASSWORD#> 

Conéctate aquí: <#LOGIN#>

Por favor, ten cuidado de usar la información correcta (nombre de usuario o dirección de correo electrónico ) para entrar en la web.

------------------------------------------------
CAMBIAR TU CONTRASEÑA 
------------------------------------------------ 

Una vez que te has conectado, te puedes dirigir a tu Panel de Control para cambiar tu contraseña. 

Panel de Control: <#THE_LINK#>",
'mod_approved_post' => "Hello!

This message is to notify you that one of your posts was approved in '<#TOPIC#>'.

The topic can be found here: <#TOPIC_URL#>


If you no longer wish to receive notifications of mod actions, you can adjust your preferences on the board by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>",
'mod_approved_topic' => "Hello!

This message is to notify you that one of your topics, '<#TOPIC#>', has been approved by a moderator.

The topic can be found here: <#TOPIC_URL#>


If you no longer wish to receive notifications of mod actions, you can adjust your preferences on the board by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>",
'mod_a_mod' => "A moderator",
'mod_closed_topic' => "Hello!

This message is to notify you that one of your topics, '<#TOPIC#>', has been closed by a moderator.

The topic can be found here: <#TOPIC_URL#>


If you no longer wish to receive notifications of mod actions, you can adjust your preferences on the board by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>",
'mod_deleted_post' => "Hello!

This message is to notify you that one of your posts has been deleted from '<#TOPIC#>'.


If you no longer wish to receive notifications of mod actions, you can adjust your preferences on the board by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>",
'mod_deleted_topic' => "Hello!

This message is to notify you that one of your topics, '<#TOPIC#>', has been deleted by a moderator.


If you no longer wish to receive notifications of mod actions, you can adjust your preferences on the board by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>",
'mod_moved_topic' => "Hello!

This message is to notify you that one of your topics has been moved.

The topic can be found here: <#TOPIC_URL#>


If you no longer wish to receive notifications of mod actions, you can adjust your preferences on the board by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>",
'mod_opened_topic' => "Hello!

This message is to notify you that one of your topics, '<#TOPIC#>', has been opened by a moderator.

The topic can be found here: <#TOPIC_URL#>


If you no longer wish to receive notifications of mod actions, you can adjust your preferences on the board by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>",
'newemail' => "<#NAME#>
Este correo ha sido enviado desde <#BOARD_ADDRESS#>. 

Has recibido este mensaje porque has pedido un cambio de dirección de correo electrónico. 

------------------------------------------------
Instrucciones de Activación a continuación
------------------------------------------------ 

Se requiere que \"valides\" el cambio de tu dirección de correo electrónico para asegurarnos de que has sido tú quien ha promovido esta acción. Esto protege contra el spam no deseado y el abuso malicioso. 

Para activar tu cuenta, simplemente haz clic en el siguiente enlace: <#THE_LINK#>

(Con algunos programas de correo electrónico puedes tener que copiar y pegar el enlace en tu navegador web). 

------------------------------------------------
 No funciona ?
 ------------------------------------------------ 

Si no puedes validar tu inscripción pulsando sobre el enlace, por favor, visita esta página: 

<#MAN_LINK#> 

Se te pedirá un número de identificación de usuario y tu clave de validación. Estos se muestran a continuación:

Nombre de usuario: <#ID#> 
Clave de Validación: <#CODE#> 

Por favor, copia y pega, o escribe los números en los campos correspondientes en el formulario. Una vez que la activación se complete, puede que sea necesario volver a iniciar la sesión para actualizar los permisos de grupo. 

------------------------------------------------ 
Ayuda! Me aparece un error! 
------------------------------------------------

Si no puedes volver a activar tu cuenta, es posible que la cuenta ha sido suprimida o que está en proceso de otra activación, tales como registrar o cambiar tu dirección de correo electrónico registrada. Si este es el caso, entonces por favor completa la activación anterior. Si el error persiste, pónte en contacto con un administrador para corregir el problema.",
'new_comment_added' => "<#MEMBERS_DISPLAY_NAME#>, 

<#COMMENT_NAME#> te ha dejado un comentario en tu perfil. 

Gestiona tus comentarios: <#LINK#>  ",
'new_comment_request' => "<#MEMBERS_DISPLAY_NAME#>,

<#COMMENT_NAME#> ha dejado un comentario que necesita tu aprobación. Has escogido aprobar todos los nuevos comentarios, este nuevo comentario no aparecerá en tu perfil hasta que no sea aprobado. 

Conéctate y gestiona tus comentarios en: <#LINK#>",
'new_friend_added' => "<#MEMBERS_DISPLAY_NAME#>, 

<#FRIEND_NAME#> te ha añadido correctamente a su lista de amigos. 

Gestiona tus amigos: <#LINK#>  ",
'new_friend_approved' => "<#MEMBERS_DISPLAY_NAME#>, 

<#FRIEND_NAME#> ha aprobado tu petición de amigo.

Conéctate y gestiona tus amigos: <#LINK#>  ",
'new_friend_request' => "<#MEMBERS_DISPLAY_NAME#>, 

<#FRIEND_NAME#> quiere ser tu amigo. 

Este mensaje se te ha enviado porque <#FRIEND_NAME#> te ha añadido a su lista de amigos. 

Has pedido de aprobar las peticiones de amigos, tienes que visitar tu lista de amigos y aprobarlos.

Conéctada y gestiona tus amigos en: <#LINK#>",
'new_likes' => "Hola!

<#MEMBER_NAME#> te gusta un Post que creastes!

<#SHORT_POST#>
<#URL#>",
'new_post_queue_notify' => "¡Hola! 
Este email se te ha enviado desde : <#BOARD_NAME#>.

Un nuevo mensaje ha entrado en la cola de moderación y está esperando la aprobación. 

---------------------------------- 
Tema: <#TOPIC#> 
Foro: <#FORUM#> 
Autor: <#POSTER#> 
Hora: <#DATE#> 
Gestión de Cola: <#LINK#> 
---------------------------------- 

Si no necesitas notificaciones, puedes parar estos emails eliminando tu dirección de email de tus opciones de configuración de foros. 

<#BOARD_ADDRESS#>  ",
'new_status' => "<#NAME#>,

<#OWNER#> ha acaba de publicar una actualización de un nuevo estatuto.

================================================== ====================
<#STATUS#>
================================================== ====================


Puede desactivar la notificación de estado visitando <# URL #>",
'new_topic_queue_notify' => "¡Hola! 

Este email se te ha enviado desde : <#BOARD_NAME#>. 

Un nuevo tema ha entrado en la cola de moderación y está esperando la aprobación.
 ----------------------------------

Tema: <#TOPIC#>
Foro: <#FORUM#> 
Autor: <#POSTER#>
Hora: <#DATE#> 
Gestión de Cola: <#LINK#>
 ---------------------------------- 

Si no necesitas notificaciones, puedes parar estos emails eliminando tu dirección de email de tus opciones de configuración de foros. 

<#BOARD_ADDRESS#>  ",
'personal_convo_invite' => "<#NAME#>,

<#POSTER#> te ha añadido a su conversación personal titulada \"<#TITLE#>\". 

Tu puedes leer la conversación personal siguiendo al siguiente enlace: <#BOARD_ADDRESS#><#LINK#>  ",
'personal_convo_new_convo' => "<#NAME#>, 

<#POSTER#> te ha enviado una nueva conversación personal titulada \"<#TITLE#>\". 

<#POSTER#> dijo: ======================================================================
<#TEXT#> 
======================================================================

Puedes ver esta conversación personal siguiendo el siguiente enlace: 

<#BOARD_ADDRESS#><#LINK#>  ",
'personal_convo_new_reply' => "<#NAME#>,

<#POSTER#> ha contestado en una conversación personal titulada \"<#TITLE#>\". 

<#POSTER#> dijo: 
====================================================================== 

<#TEXT#> 
====================================================================== 

Puedes ver esta conversación personal siguiendo al siguiente enlace: 

<#BOARD_ADDRESS#><#LINK#>  ",
'possibleSpammer' => "Hola,

has recibido este email porque has escogido que se te avise cuando haya un posible spammer. 

Nombre: <#MEMBER_NAME#> 
Email: <#EMAIL#> 
IP: <#IP#>
Registrado: <#DATE#> 

Puedes ver este usuario aquí: <#LINK#> 

Que tengas un buen día!",
'post_mentions' => "Hello!

This message is to notify you that <#MEMBER_NAME#> mentioned you in a post.

The post that <#MEMBER_NAME#> submitted can be found here:

<#POST_LINK#>

----------------------------------
<#POST#>
----------------------------------

If you no longer wish to receive notifications of quoted posts, you can adjust your preferences on the
community by clicking My Settings, and then choosing Notification Options.

<#BOARD_ADDRESS#>
",
'post_was_quoted' => "Hola!

Este mensaje es para notificarle que uno de sus mensajes ha sido citado por <#MEMBER_NAME#>.

El puesto que fue citado se puede encontrar aquí:

<#ORIGINAL_POST#>

El puesto que <#MEMBER_NAME#> presentado se puede encontrar aquí:

<#NEW_POST#>

----------------------------------
<#POST#>
----------------------------------

Si ya no desea recibir notificaciones de los puestos citados, se puede ajustar sus preferencias sobre la
Mi tablero haciendo clic en Configuración y, a continuación, elija Opciones de notificación.

<#BOARD_ADDRESS#>",
'reg_validate' => "<#NAME#>,
Este correo ha sido enviado desde <#BOARD_ADDRESS#>.

Has recibido este mensaje porque esta dirección de correo electrónico se utilizó durante el registro en nuestra web. 
Si no te registras en nuestra web, por favor ignora este mensaje. No es necesario darse de baja o tomar cualquier otra acción. 


------------------------------------------------ 
Instrucciones de Activación 
------------------------------------------------ 

Gracias por registrarte. 
Es necesario que \"valides\" tu inscripción para asegurarnos de que la dirección de correo electrónico que has introducido es correcta. Esto te protege contra el spam no deseado y malicioso abuso. 

Para activar tu cuenta, simplemente haz clic en el siguiente enlace: 

<#THE_LINK#> 

Una vez hayas creado tu nombre de usuario, el mismo te permitirá identificarte como usuario del sitio con la contraseña: <#CODE#>

(Algunos programas de correo electrónico pueden tener que copiar y pegar el enlace en tu navegador web). 

Si todavía no puedes validar tu cuenta, es posible que la cuenta se ha eliminado. 
Si este es el caso, por favor, ponte en contacto con un administrador para corregir el problema. 

Gracias por registrarte y ¡¡¡disfruta de tu estancia con nosotros!!!",
'send_text' => "Pensaba que te podría interesar leer esta página web: <#THE LINK#> 

De, 

<#USER NAME#>  ",
'status_reply' => "<#NAME#>,

<#POSTER#> <#BLURB#>

Status: (<#OWNER#>) <#STATUS#>
======================================================================
<#TEXT#>
======================================================================


Puede desactivar la notificación de estado visitando <#URL#>
",
'subject__account_created' => "Se ha creado tu cuenta",
'subject__complete_reg' => "Cuenta: %s, validada en %s",
'subject__digest_forum_daily' => "Tu resumen diario de nuevos temas",
'subject__digest_forum_weekly' => "Tu resumen semanal de nuevos temas",
'subject__digest_topic_daily' => "Tu resumen diario de nuevos mensaje",
'subject__digest_topic_weekly' => "Tu resumen semanal de nuevos mensaje",
'subject__email_convo' => "Archivo de Conversación Personal",
'subject__error_log_notification' => "Se ha generado un error en tu web",
'subject__mod_approved_post' => "%s approved a post of yours in <a href='%s'>%s</a>",
'subject__mod_approved_topic' => "%s approved <a href='%s'>%s</a>",
'subject__mod_closed_topic' => "%s closed <a href='%s'>%s</a>",
'subject__mod_deleted_post' => "%s elimino tu mensaje de <a href='%s'>%s</a> ",
'subject__mod_deleted_topic' => "%s elimino \"%s\"<a href=\"#\"></a> ",
'subject__mod_moved_topic' => "%s moved <a href='%s'>%s</a> to <a href='%s'>%s</a>",
'subject__mod_opened_topic' => "%s opened <a href='%s'>%s</a>",
'subject__new_comment_added' => "Nuevo Comentario",
'subject__new_comment_request' => "Nuevo Comentario Pendiente de Aprobación",
'subject__new_friend_added' => "<a href='%s'>%s</a> te añadio como Amigo",
'subject__new_friend_approved' => "Aprobación de Nuevo Amigo",
'subject__new_friend_request' => "Petición de Nuevo Amigo",
'subject__new_likes' => "<a href='%s'>%s</a> le gustó <a href='%s'>el Post que tu creastes</a> en <a href='%s'>%s</a>",
'subject__new_post_queue_notify' => "Nuevo Mensaje Pendiente de Aprobación",
'subject__new_status' => "<a href='%s'><#OWNER#></a> ha publicado un nuevo <a href='%s'>estado actualizado</a>",
'subject__new_topic_queue_notify' => "Nuevo Tema Pendiente de Aprobación",
'subject__other_status_reply' => "<a href='%s'>%s</a> ha publicado en <a href='%s'>%s</a>'s <a href='%s'>estatus</a>  ",
'subject__personal_convo_invite' => "Se te ha añadido en una conversación personal",
'subject__personal_convo_new_convo' => "Se te ha enviado a una nueva conversación personal",
'subject__personal_convo_new_reply' => "Se ha escrito una respuesta a una conversación personal",
'subject__post_mentions' => "<a href='%s'>%s</a> te ha mencionado en <a href='%s'> este mensaje</a> ",
'subject__post_was_quoted' => "<a href='%s'>%s</a> <a href='%s'>Citar</a> a <a href='%s'>mensaje que usted hizo</a>",
'subject__status_reply' => "<a href='%s'>%s</a> nueva respuesta en tu <a href='%s'>estatus</a>  ",
'subject__subs_new_topic' => "[<#TITLE#>] Nuevo Tema",
'subject__subs_no_post' => "[<#TITLE#>] Nueva Respuesta",
'subject__subs_with_post' => "[<#TITLE#>] Nueva Respuesta",
'subject__subs_with_post.emailOnly' => "New reply to %s",
'subject__their_status_reply' => "<a href='%s'>%s</a> has made a reply to their <a href='%s'>status</a>",
'subs_new_topic' => "<#NAME#>, 

<#POSTER#> ha escrito un nuevo tema titulado \"<#TITLE#>\" en el foro \"<#FORUM#>\". 

----------------------------------------------------------
 <#POST#> 
----------------------------------------------------------

 El tema se puede encontrar aquí: <#BOARD_ADDRESS#>?showtopic=<#TOPIC_ID#>

Por favor, piensa que si quieres tener notificaciones por email de las respuestas de este tema, tendrás que pulsar en el vínculo \"Seguir este Tema\" mostrado en el tema de esta página, o visitando el vínculo siguiente: 
<#BOARD_ADDRESS#>?act=Track&f=<#FORUM_ID#>&t=<#TOPIC_ID#>

Cancelación de la subscripción: 
-------------- 

Puedes anular la subscripción en cualquier momento yendo a tu panel de control y pulsando en el vínculo \"Administrar Foros Vistos\" en la ficha de \"Foros\". Si no estás suscrito a foros y deseas parar de recibir notificaciones, desmarca la opción \"Enviarme actualizaciones enviadas por el administrador de la web\" que encontrarás en \"Mis Opciones\" baja \"Opciones Generals\" en la pestaña \"Configuración\".",
'subs_no_post' => "<#NAME#>, 

<#POSTER#> ha escrito una respuesta a un tema que estabas suscrito con tema \"<#TITLE#>\".

El tema lo encontrarás aquí: <#BOARD_ADDRESS#>?showtopic=<#TOPIC_ID#>&view=getnewpost 

Pueden haber más respuestas al tema, pero sólo se envía 1 email por visita para cada tema suscrito. Esto es para limitar la cantidad de mail que se te envíe a tu Bandeja de Entrada. 

Cancelar suscripción:
 --------------


Puedes cancelar tu sucripción en cualquier momento conectándote a tu panel de control y pulsando  en la \"Gestión de los temas Vistos\" en la pestaña de los \"Foros\".",
'subs_with_post' => "<#NAME#>, 

<#POSTER#> acaba de escribir una respuesta a un tema que te has suscrito con título \"<#TITLE#>\". 

--------------------------------------------------
 <#POST#>
 --------------------------------------------------


El tema se puede encontrar aquí: <#BOARD_ADDRESS#>? showtopic=<#TOPIC_ID#>&view=getnewpost 

Si has configurado en tu panel de control para recibir notificaciones de respuesta inmediatamente del tema, puede recibir un mensaje de correo electrónico para cada respuesta a este tema. En caso contrario, sólo se envía 1 correo electrónico por cada visita a la web para cada tema suscrito. Esto es para limitar la cantidad de correo que se envía a tu bandeja de entrada. 

Cancelación de la suscripción:
 --------------

 Puedes cancelar tu suscripción en cualquier momento accediendo a tu panel de control y haciendo clic en la \"Gestión de los temas vistos\" en la ficha \"Foros\".",
 ); 
