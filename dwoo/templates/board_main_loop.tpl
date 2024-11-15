{* INIT SECTION, STARTING LOOPS *}
 {if not $for_overboard}
 <form id="delform_instant" action="{%KU_CGIPATH}/board.php" method="post">
 <input type="hidden" name="board" value="{$board.name}" />
 {/if}
 {foreach name=thread item=posts_in_thread from=$posts}
  {foreach key=postkey item=post from=$posts_in_thread}
{* / INIT SECTION *}

{* PRE-POSTHEAD SECTION *}
 {if $post.parentid eq 0} {* If OP → *}
  {if $isthread}<div class="i0svcel">!i0-pd:{$post.id}</div>{/if} {* post delimiter for quick parsing *}
  <span id="unhidethread{$post.id}-{$board.name}">
   {t}Thread{/t} 
   <a href="{%KU_BOARDSFOLDER}{$board.name}/res/{$post.id}.html" class="ref|{$board.name}|{$post.id}|{$post.id}">{if $for_overboard}/{$board.name}/{/if}{$post.id}</a> 
   {t}hidden.{/t}
   <a href="#" onclick="javascript:HiddenItems.unhideThread('{$post.id}-{$board.name}');return false;" title="{t}Un-Hide Thread{/t}">
    <svg class="icon b-icon"><use xlink:href="#i-unhide"></use></svg>
   </a>
  </span>
  {if $post.IS_DELETED}
	{$thread_deleted = 1}
	{if $isthread}<h2 class="deleted_thread"> Тред удалён </h2>{/if}
	<details class="deleted">
		<summary class="deleted" title="Тред удалён"></summary>
	{/if}
  <div id="thread{$post.id}-{$board.name}" data-threadid="{$post.id}" class="{if $isthread}replies {/if}deleted_{$post.IS_DELETED}" data-boardid="{$board.id}"> {* #thread → *}
   <div class="postnode op" data-id="{$post.id}" data-board="{$board.name}"> {* .postnode.op → *}
    <a name="s{$.foreach.thread.iteration}"></a>
 {else} {* If reply → *}
  {if $isthread}<div class="i0svcel">!i0-pd:{$post.id}</div>{/if} {* post delimiter for quick parsing *}
  {if $post.IS_DELETED}
  {$post_deleted = 1}
  <details class="deleted">
    <summary class="deleted" title="Пост удалён"></summary>
  {/if}
  <table id="postnode{$post.id}-{$board.name}" class="postnode deleted_{$post.IS_DELETED}" data-id="{$post.id}" data-board="{$board.name}"><tbody>
   <tr>
    <td class="doubledash">&gt;&gt;</td>
    <td class="reply" id="reply{$post.id}"> {* td.reply → *}
     <a name="{$post.id}"></a>
 {/if}
{* / PRE-POSTHEAD SECTION *}

{* POSTHEAD SECTION *}
 <div class="posthead{if $post.parentid eq 0}{if $post.locked eq 1} thread-locked{/if}{if $post.stickied eq 1} thread-stickied{/if}{/if}">
  {if $post.parentid eq 0 and $for_overboard}<a href="/{$board.name}" target="_blank" class="over-boardlabel">/{$board.name}/ — {$board.desc}</a>{/if}
  <label class="postinfo">
   <svg class="icon b-icon post-menu-toggle yesscript"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#i-dots"></use></svg>
   <input type="checkbox" name="post[]" class="multidel noscript" value="{$post.id}:{$board.name}" />
   {if $post.subject neq ''}
    <span class="filetitle">{$post.subject}</span>
   {/if}
   {strip}
    <span class="postername">
     {if $post.name eq '' && $post.tripcode eq ''}
      {$board.anonymous}
     {elseif $post.name eq '' && $post.tripcode neq ''}
     {else}
      {$post.name}
     {/if}
    </span>
    {if $post.tripcode neq ''}
     <span class="postertrip">!{$post.tripcode}</span>
    {/if}
   {/strip}
   {if $post.posterauthority eq 1}
    <span class="admin">&#35;&#35;&nbsp;{t}Admin{/t}&nbsp;&#35;&#35;</span>
   {elseif $post.posterauthority eq 2}
    <span class="mod">&#35;&#35;&nbsp;{t}Mod{/t}&nbsp;&#35;&#35;</span>
   {elseif $post.posterauthority eq 3}
    <span class="admin">&#35;&#35;&nbsp;{t}Board owner{/t}&nbsp;&#35;&#35;</span>
   {/if}
   <span class="posttime">
    {$post.timestamp_formatted}
   </span>
  </label>
  <span class="reflink" data-id="{$post.id}" data-b="{$board.name}">{$post.reflink}</span>
  {if $post.ttl}
    <span class="post-ttl" data-deleted-timestamp="{$post.deleted_timestamp}" title="{t}To be deleted on{/t} {$post.deleted_timestamp_formatted}">{$post.ttl}</span>
  {/if}
  {if $board.showid}
   <img class="hashpic" src="data:image/gif;base64,{rainbow($post.hash_id)}" alt="{$post.hash_id}">
  {/if}
  <span class="extrabtns yesscript">
   {if $post.parentid eq 0} {* Extra-buttons related to OP only → *}
    {if $post.locked eq 1}
     <svg class="icon i-icon i-lock"><use xlink:href="#i-lock"></use></svg>
    {/if}
    {if $post.stickied eq 1}
     <svg class="icon i-icon i-pin"><use xlink:href="#i-pin"></use></svg>
    {/if}
    <span id="hide{$post.id}" class="inthread-hide">
     <a href="#" onclick="javascript:HiddenItems.hideThread('{$post.id}-{$board.name}');return false;" title="{t}Hide Thread{/t}">
      <svg class="icon b-icon"><use xlink:href="#i-hide"></use></svg>
     </a>
    </span>
   {/if} {* ← /Extra-buttons related to OP only *}
   {if $post.parentid > 0}
    <span id="hidepost{$post.id}-{$board.name}">
        <a href="#" onclick="javascript:HiddenItems.hidePost('{$post.id}-{$board.name}');return false;" title="{t}Hide Post{/t}">
            <svg class="icon b-icon"><use xlink:href="#i-hide"></use></svg>
        </a>
    </span>
    <span id="unhidepost{$post.id}-{$board.name}">
     {t}Post{/t} 
     <a href="{%KU_BOARDSFOLDER}{$board.name}/res/{$post.id}.html" class="ref|{$board.name}|{$post.id}|{$post.id}">{if $for_overboard}/{$board.name}/{/if}{$post.id}</a> 
     {t}hidden.{/t}
     <a href="#" onclick="javascript:HiddenItems.unhidePost('{$post.id}-{$board.name}');return false;" title="{t}Un-Hide Post{/t}">
      <svg class="icon b-icon"><use xlink:href="#i-unhide"></use></svg>
     </a>
    </span>
   {/if}
   <a href="#" 
   data-parent="{if $post.parentid eq 0}{$post.id}{else}{$post.parentid}{/if}-{$board.name}" 
    data-postnum="{$post.id}"
   class="qrl" 
   title="{strip}{t}Quick Reply{/t}
    {if $post.parentid neq 0}
     &nbsp;{t}in thread{/t} {$post.parentid}
    {/if}
   {/strip}">
    <svg class="icon b-icon"><use xlink:href="#i-qr"></use></svg>
   </a>
   {if $board.balls}
    <img loading="lazy" class="_country_" src="{%KU_WEBPATH}/images/flags/{$post.country}.png">
   {/if}
  </span>
  {if $post.parentid eq 0}
   {strip}<span class="inthread-hide">[<a href="{%KU_BOARDSFOLDER}{$board.name}/res/{if $post.parentid eq 0}{$post.id}{else}{$post.parentid}{/if}.html">
    {if $post.locked eq 1}
     {t}Enter{/t}
    {else}
     {t}Reply{/t}
    {/if}
   </a>]</span>{/strip}
   {* Unmaintained firstlast shit → *}
   {if %KU_FIRSTLAST && (($post.stickied eq 1 && $post.replies + %KU_REPLIESSTICKY > 50) || ($post.stickied eq 0 && $post.replies + %KU_REPLIES > 50))}
    {if (($post.stickied eq 1 && $post.replies + %KU_REPLIESSTICKY > 100) || ($post.stickied eq 0 && $post.replies + %KU_REPLIES > 100))}
     [<a href="{%KU_BOARDSFOLDER}{$board.name}/res/{if $post.parentid eq 0}{$post.id}{else}{$post.parentid}{/if}-100.html">{t}First 100 posts{/t}</a>]
    {/if}
    [<a href="{%KU_BOARDSFOLDER}{$board.name}/res/{$post.id}+50.html">{t}Last 50 posts{/t}</a>]
   {/if}
   {* ← /Unmaintained firstlast shit *}
   <br>
  {else}
   {if $post.embeds}<br>{/if}
  {/if}
 </div>
{* / POSTHEAD SECTION *}

{* POSTBODY+POSTBUTT SECTION *}
 <div id="postbody{$post.id}-{$board.name}" class="postbody{if $post.parentid neq 0 && !$post.message} pb-empty{/if}{if $post.parentid eq 0 && mb_strlen($post.message) > 1000} pb-long{/if}" data-htmlength="{mb_strlen($post.message)}">
  {if $post.embeds}
   <div class="embedgroup">
    {foreach item=embed from=$post.embeds name=embeds}
     {if $embed.file neq 'removed' && !$embed.IS_FILE_DELETED || $post.deleted_files < 2}
      {if $embed.file eq 'removed' || $embed.IS_FILE_DELETED }
       <div class="nothumb">
        {t}File<br />Removed{/t}
       </div>
      {else}
       <figure class="multiembed{if $embed.is_embed} video-embed{/if}{if $embed.spoiler} f-spoiler{/if}" data-fileid="{$embed.file_id}">
        {if $embed.spoiler}
         <input type="checkbox" class="spoiler-checkbox" id="emb-{$embed.file_id}-spoiler">
         <label for="emb-{$embed.file_id}-spoiler" title="{t}Spoiler{/t}"><div class="spoiler-cover"><div>
          <svg class="icon yesscript">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#i-spoiler"></use>
          </svg><br class="yesscript">
          <noscript>{t}Spoiler{/t}</noscript>
         </div></div></label>
        {/if}
        {if !$embed.is_embed}
         {assign var="filename" value=get_embed_filename($embed)}
         <figcaption class="filesize">
          {strip}<a download="{$filename}.{$embed.file_type}" {if %KU_NEWWINDOW}target="_blank"{/if} href="{$file_path}/src/{$embed.file}.{$embed.file_type}" title="{$filename}{if $embed.id3.comments_html.title.0 eq ''}.{$embed.file_type}{/if}">
           {if %I0_DETECT_SOSACH}{if is_from_sosach($filename)}<img loading="lazy" class="sosach_indicator" src="/images/2chfav.ico">{/if}{/if}
           <span class="fc-filename"{* set_max_filename_width($embed.thumb_w, $embed.id3.comments_html.title.0) *}>
            {$filename}
           </span>
           {if $embed.id3.comments_html.title.0 eq ''}
            .{$embed.file_type}
           {/if}
          </a>{/strip}
          <br>{strip}
          {if $embed.generic_icon eq '' and $embed.id3.comments_html.title.0 neq ''}
           {$embed.file_type|upper},{" "}
          {/if}
          {$embed.file_size_formatted}
          {if $embed.image_w > 0 && $embed.image_h > 0}
           , {$embed.image_w}×{$embed.image_h}
          {/if}
          {if $embed.id3.playtime_string neq ''}
           , {$embed.id3.playtime_string}
          {/if}{/strip}
          <button type="submit" class="yesscript file-control file-menu-toggle emb-button post-menu-toggle" value="{$embed.file_id}">
           <svg class="icon b-icon"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#i-dots"></use></svg>
          </button>
          <input title="{t}Delete file{/t}" type="checkbox" name="delete-file[]" class="file-control emb-button noscript multidel" value="{$embed.file_id}:{$board.name}">
         </figcaption>
        {else}
         <div class="embed-wrap">
          <div class="emb-iframe-wrapper" data-w="{$embed.image_w}" data-h="{$embed.image_h}" data-code="{$embed.file}" data-site="{$embed.site_name}"{if $embed.start} data-start="{$embed.start}" data-startraw="{$embed.file_size}"{/if}></div>
          <div class="embed-overlay"></div>
          <img loading="lazy" class="embed-thumbnail" src="{$file_path}/thumb/{str_replace('/', '_', $embed.thumbnail)}" alt="" height="{$embed.thumb_h}" width="{$embed.thumb_w}">
          <div class="embed-title">
           <a href="{$embed.videourl}" target="_blank" title="{$embed.file_original} — {t}Watch on{/t} {$embed.site_name}">{$embed.file_original}</a>
          </div>
          {if $embed.file_size_formatted neq '0'}<div class="embed-duration">{$embed.file_size_formatted}</div>{/if}
          <img loading="lazy" src="{%KU_BOARDSFOLDER}images/site-logos/{strtolower($embed.site_name)}.png" alt="" class="embed-logo">
          <button type="submit" class="emb-button yesscript file-control file-menu-toggle emb-button" value="{$embed.file_id}">
           <svg class="icon"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#i-dots"></use></svg>
          </button>
          <input title="{t}Delete file{/t}" type="checkbox" name="delete-file[]" class="file-control emb-button noscript multidel" value="{$embed.file_id}:{$board.name}">
          <a href="{$embed.videourl}" class="embed-play-button" title="{t}Play{/t}"></a>
         </div>
        {/if}
        {if !$embed.is_embed && $embed.file neq '' && ( $embed.file_type eq 'jpg' || $embed.file_type eq 'gif' || $embed.file_type eq 'png')}
         <a 
         {if %KU_NEWWINDOW}
          target="_blank" 
         {/if}
         onclick="javascript:return expandimg('{$embed.file_id}', '{$file_path}/src/{$embed.file}.{$embed.file_type}', '{$file_path}/thumb/{$embed.file}s.{$embed.file_type}', '{$embed.image_w}', '{$embed.image_h}', '{$embed.thumb_w}', '{$embed.thumb_h}');" 
         href="{$file_path}/src/{$embed.file}.{$embed.file_type}">
         <span id="thumb{$embed.file_id}"><img loading="lazy" src="{$file_path}/thumb/{$embed.file}s.{$embed.file_type}" alt="{$post.id}" class="thumb" height="{$embed.thumb_h}" width="{$embed.thumb_w}" /></span>
         </a>
        {elseif $embed.nonstandard_file neq ''}
         <a 
         {if $embed.file_type eq 'webm' or $embed.file_type eq 'mp4'} class="movie" data-id="{$post.id}" data-thumb="{$embed.nonstandard_file}" data-width="{$embed.image_w}" data-height="{$embed.image_h}"{/if}
         {if $embed.file_type eq 'mp3' or $embed.file_type eq 'ogg'} class="audiowrap" {/if}
         {if $embed.file_type eq 'css'} class="csswrap" {/if}
         {if %KU_NEWWINDOW}target="_blank"{/if}        
         href="{$file_path}/src/{$embed.file}.{$embed.file_type}">
         <div id="thumb{$post.id}"{if $embed.generic_icon eq ''} class="thumb playable-thumb" title="{t}Play{/t}"{/if}><img loading="lazy" src="{$embed.nonstandard_file}" alt="{$post.id}" class="thumb" height="{$embed.thumb_h}" width="{$embed.thumb_w}" /></div>
         </a>
        {/if}
       </figure>
      {/if}
     {/if}
    {/foreach}
    {if $post.deleted_files >= 2}
     <div class="nothumb">
      {$post.deleted_files}
      {assign var="multiple_files_removed" value="1"}
      {if $locale == 'ru'}
       {assign var="files_declensed" value=declense($post.deleted_files)}
       {if $files_declensed eq 0}файлов
       {elseif $files_declensed eq 1}файл
       {elseif $files_declensed eq 2}файла{/if}
       <br>
       {if $files_declensed eq 1}удален{else}удалено{/if}.
      {else}
      {t}Files<br />Removed{/t}
      {/if}
     </div>
    {/if}
   </div>
  {/if}
  <blockquote class="postmessage">{$post.message}</blockquote>
  {if not $post.stickied && $post.parentid eq 0 && (($board.maxage > 0 && ($post.timestamp + ($board.maxage * 3600)) < (time() + 7200 ) ) || ($post.deleted_timestamp > 0 && $post.deleted_timestamp <= (time() + 7200)))} {* Fucking mouth of this logic... *}
   <span class="oldpost">
    {t}Marked for deletion (old){/t}
   </span>
   <br />
  {/if}
 </div>
 <div class="postbutt replieslist"></div>
{* / POSTBODY+POSTBUTT SECTION *}

{* WRAP-UP SECTION *}
 {if $post.parentid eq 0}
  </div> {* ← /.postnode.op (at least I hope so) *}
  {if not $isthread}
   <div id="replies{$post.id}-{$board.name}" class="replies deleted_{$post.IS_DELETED}">
   {if $post.replies}
    <span class="omittedposts">
     <a href="{%KU_BOARDSFOLDER}{$board.name}/res/{if $post.parentid eq 0}{$post.id}{else}{$post.parentid}{/if}.html" onclick="return expandthread('{if $post.parentid eq 0}{$post.id}{else}{$post.parentid}{/if}','{$board.name}', event)" title="{t}Expand Thread{/t}">
      {if $locale == 'ru'}
       {omitted_syntax($post.replies, $post.images)}
      {else}
       {if $post.stickied eq 0}
        {$post.replies}
        {if $post.replies eq 1}
         {t lower="yes"}Post{/t} 
        {else}
         {t lower="yes"}Posts{/t} 
        {/if}
       {else}
        {$post.replies}
        {if $post.replies eq 1}
         {t lower="yes"}Post{/t} 
        {else}
         {t lower="yes"}Posts{/t} 
        {/if}
       {/if}
       {if $post.images > 0}
        {t}and{/t} {$post.images}
        {if $post.images eq 1}
         {t lower="yes"}Image{/t} 
        {else}
         {t lower="yes"}Images{/t} 
        {/if}
       {/if}
       {t}omitted{/t}.
      {/if}
     </a>
    </span>
   {/if}
  {else}
   {if $modifier eq 'last50'}
    <span class="omittedposts">
      {$replycount-50}
      {if $replycount-50 eq 1}
        {t lower="yes"}Post{/t} 
      {else}
        {t lower="yes"}Posts{/t} 
      {/if}
    {t}omitted{/t}. {t}Last 50 shown{/t}.
    </span>
   {/if}
   {* if $numimages > 0}
    <a href="#top" onclick="javascript:
    {foreach key=postkey2 item=post2 from=$posts}
     {if $post2.parentid neq 0}
      {if $post2.file_type eq 'jpg' || $post2.file_type eq 'gif' || $post2.file_type eq 'png'}
       expandimg('{$post2.id}', '{$file_path}/src/{$post2.file}.{$post2.file_type}', '{$file_path}/thumb/{$post2.file}s.{$post2.file_type}', '{$post2.image_w}', '{$post2.image_h}', '{$post2.thumb_w}', '{$post2.thumb_h}');
      {/if}
     {/if}
    {/foreach}
    return false;">{t}Expand all images{/t}</a>
   {/if *}
  {/if}
 {else}
    </td> {* ← /reply *}
   </tr>
  </tbody></table> {* ← .postnode *}
  {if $post_deleted}
  {$post_deleted = 0}
  </details>
  {/if}
 {/if}
 {if $isthread}<div class="i0svcel">!i0-pd-end</div>{/if} {* post delimiter for quick parsing *}
 {/foreach} 
 {if not $isthread}
  </div> {* ← I've given up at this point *}
  </div>
  {if $thread_deleted}
  </details>
  {/if}
  {if $thread_deleted}
  {else}
  <br clear="left" />
  <hr />
  {/if}
  {if $thread_deleted}
    {$thread_deleted = 0}
  {/if}
 {else}
  {if $modifier eq 'first100'}
   <span class="omittedposts" style="float: left">
    {$replycount-100}
    {if $replycount-100 eq 1}
     {t lower="yes"}Post{/t} 
    {else}
     {t lower="yes"}Posts{/t} 
    {/if}
    {t}omitted{/t}. {t}First 100 shown{/t}.
   </span>
  {/if}
  {if $replycount > 2}
   <span style="float:right">
    &#91;<a href="/{$board.name}/">{t}Return{/t}</a>&#93;
    {if %KU_FIRSTLAST && ( count($posts) > 50 || $replycount > 50)}
     &#91;<a href="/{$board.name}/res/{$posts.0.id}.html">{t}Entire Thread{/t}</a>&#93; 
     &#91;<a href="/{$board.name}/res/{$posts.0.id}+50.html">{t}Last 50 posts{/t}</a>&#93;
     {if ( count($posts) > 100 || $replycount > 100) }
      &#91;<a href="/{$board.name}/res/{$posts.0.id}-100.html">{t}First 100 posts{/t}</a>&#93;
     {/if}
    {/if}
   </span>
  {/if}
  </div>{* Huh? *}
  {if $thread_deleted}
  </details>
  {/if}
  <br clear="left" />
  <div id="newposts_get"><!-- 
  --><a href="#" onclick="return newposts.get()" title="А если нет, то не получать новые посты"><!-- 
    --><svg class="icon b-icon refresher">
       <use xlink:href="#i-refresh"></use>
       <g class="tc-wrapper">
        <circle cx="50%" cy="50%" r="55%" style="" class="timer-circle"></circle>
       </g>
      </svg><!-- 
  --><span class="upd-action">{t}Get new posts{/t}</span><!-- 
  --></a><span class="upd-msg">{t}Loading{/t}</span>
  </div>
  <hr />
 {/if}
 {/foreach}
{* / WRAP-UP SECTION *}
