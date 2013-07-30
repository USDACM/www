<?php include (TEMPLATEPATH . '/options.php'); ?>
<?php
if (file_exists(ABSPATH . WPINC . '/feed.php')) {
require_once (ABSPATH . WPINC . '/feed.php');
}
else if(file_exists(ABSPATH . WPINC . '/rss-functions.php')){
require_once (ABSPATH . WPINC . '/rss-functions.php');
}
?>

<?php $show_rss_network = get_option('tn_edus_rss_network_status'); ?>
<?php if($show_rss_network == 'yes'): ?>

<div class="tabber">

<?php $show_rss_one = get_option('tn_edus_feedone_status'); ?>
<?php if($show_rss_one == 'yes'): ?>

<div class="tabbertab">
<h3><?php echo "$tn_edus_network_one"; ?></h3>

<div class="rss-feeds">

<?php
$get_net_gfeed_url = $tn_edus_network_one_url;
$rss = @fetch_feed("$get_net_gfeed_url");
$msg = "";
if (!is_wp_error( $rss )) {
$maxitems = $rss->get_item_quantity($tn_edus_network_one_sum);
foreach($rss->get_items(0, $maxitems) as $item){

$feed_livelink = $item->get_permalink();
$feed_livelink = str_replace("&", "&amp;", $feed_livelink);
$feed_livelink = str_replace("&amp;&amp;", "&amp;", $feed_livelink);

$feed_authorlink = $item->get_author()->name;
$feed_categorylink = $item->get_category()->term;

$feed_livetitle = ucfirst($item->get_title());

$feed_descriptions = $item->get_description();
if ($feed_descriptions) {
$feed_descriptions = strip_tags($feed_descriptions);
$feed_descriptions = substr_replace($feed_descriptions,"...","$tn_edus_feed_word");
} else {
$feed_descriptions = '';
}

$msg .= "
<div class=\"feed-pull\"><h1>
<a href=\"".trim($feed_livelink)."\" rel=\"external nofollow\" title=\"".trim($feed_livetitle)."\">".trim($feed_livetitle)."</a>
</h1>
<div class=\"rss-author\">$feed_authorlink</div>
<div class=\"rss-content\">$feed_descriptions</div></div>\n";
}


echo "$msg";
} else {
_e("<div class=\"rss-content\">Currently there is no feed available</div>");
}
?>

</div>

</div>

<?php endif; ?>

<?php $show_rss_two = get_option('tn_edus_feedtwo_status'); ?>
<?php if($show_rss_two == 'yes'): ?>

<div class="tabbertab">
<h3><?php echo "$tn_edus_network_two"; ?></h3>
<div class="rss-feeds">
<?php
$get_net_gfeed_url2 = $tn_edus_network_two_url;
$rss2 = @fetch_feed("$get_net_gfeed_url2");
$msg2 = "";
if (!is_wp_error( $rss2 )) {
$maxitems = $rss2->get_item_quantity($tn_edus_network_two_sum);
foreach($rss2->get_items(0, $maxitems) as $item2){

$feed_livelink2 = $item2->get_permalink();
$feed_livelink2 = str_replace("&", "&amp;", $feed_livelink2);
$feed_livelink2 = str_replace("&amp;&amp;", "&amp;", $feed_livelink2);

$feed_authorlink2 = $item2->get_author()->name;
$feed_categorylink2 = $item2->get_category()->term;

$feed_livetitle2 = ucfirst($item2->get_title());

$feed_descriptions2 = $item2->get_description();
if ($feed_descriptions2) {
$feed_descriptions2 = strip_tags($feed_descriptions2);
$feed_descriptions2 = substr_replace($feed_descriptions2,"...","$tn_edus_feed_word");
} else {
$feed_descriptions2 = '';
}

$msg2 .= "
<div class=\"feed-pull\"><h1>
<a href=\"".trim($feed_livelink2)."\" rel=\"external nofollow\" title=\"".trim($feed_livetitle2)."\">".trim($feed_livetitle2)."</a>
</h1>
<div class=\"rss-author\">$feed_authorlink2</div>
<div class=\"rss-content\">$feed_descriptions2</div></div>\n";
}

echo "$msg2";
} else {
_e("<div class=\"rss-content\">Currently there is no feed available</div>");
}
?>
</div>

</div>

<?php endif; ?>

<?php $show_rss_three = get_option('tn_edus_feedthree_status'); ?>
<?php if($show_rss_three == 'yes'): ?>

<div class="tabbertab">
<h3><?php echo "$tn_edus_network_three"; ?></h3>

<div class="rss-feeds">

<?php
$get_net_gfeed_url3 = $tn_edus_network_three_url;
$rss3 = @fetch_feed("$get_net_gfeed_url3");
$msg3 = "";
if (!is_wp_error( $rss3 )) {
$maxitems = $rss3->get_item_quantity($tn_edus_network_three_sum);
foreach($rss3->get_items(0, $maxitems) as $item3){

$feed_livelink3 = $item3->get_permalink();
$feed_livelink3 = str_replace("&", "&amp;", $feed_livelink3);
$feed_livelink3 = str_replace("&amp;&amp;", "&amp;", $feed_livelink3);

$feed_authorlink3 = $item3->get_author()->name;
$feed_categorylink3 = $item3->get_category()->term;

$feed_livetitle3 = ucfirst($item3->get_title());

$feed_descriptions3 = $item3->get_description();
if ($feed_descriptions3) {
$feed_descriptions3 = strip_tags($feed_descriptions3);
$feed_descriptions3 = substr_replace($feed_descriptions3,"...","$tn_edus_feed_word");
} else {
$feed_descriptions3 = '';
}

$msg3 .= "
<div class=\"feed-pull\"><h1>
<a href=\"".trim($feed_livelink3)."\" rel=\"external nofollow\" title=\"".trim($feed_livetitle3)."\">".trim($feed_livetitle3)."</a>
</h1>
<div class=\"rss-author\">By $feed_authorlink3</div>
<div class=\"rss-content\">$feed_descriptions3</div></div>\n";
}

echo "$msg3";
} else {
_e("<div class=\"rss-content\">Currently there is no feed available</div>");
}
?>

</div>

</div>

<?php endif; ?>

<?php $show_rss_four = get_option('tn_edus_feedfour_status'); ?>
<?php if($show_rss_four == 'yes'): ?>

<div class="tabbertab">
<h3><?php echo "$tn_edus_network_four"; ?></h3>

<div class="rss-feeds">

<?php
$get_net_gfeed_url4 = $tn_edus_network_four_url;
$rss4 = @fetch_feed("$get_net_gfeed_url4");
$msg4 = "";
if (!is_wp_error( $rss4 )) {
$maxitems = $rss4->get_item_quantity($tn_edus_network_four_sum);
foreach($rss4->get_items(0, $maxitems) as $item4){

$feed_livelink4 = $item4->get_permalink();
$feed_livelink4 = str_replace("&", "&amp;", $feed_livelink4);
$feed_livelink4 = str_replace("&amp;&amp;", "&amp;", $feed_livelink4);

$feed_authorlink4 = $item4->get_author()->name;
$feed_categorylink4 = $item4->get_category()->term;

$feed_livetitle4 = ucfirst($item4->get_title());

$feed_descriptions4 = $item4->get_description();
if ($feed_descriptions4) {
$feed_descriptions4 = strip_tags($feed_descriptions4);
$feed_descriptions4 = substr_replace($feed_descriptions4,"...","$tn_edus_feed_word");
} else {
$feed_descriptions4 = '';
}

$msg4 .= "
<div class=\"feed-pull\"><h1>
<a href=\"".trim($feed_livelink4)."\" rel=\"external nofollow\" title=\"".trim($feed_livetitle4)."\">".trim($feed_livetitle4)."</a>
</h1>
<div class=\"rss-author\">By $feed_authorlink4</div>
<div class=\"rss-content\">$feed_descriptions4</div></div>\n";
}

echo "$msg4";
} else {
_e("<div class=\"rss-content\">Currently there is no feed available</div>");
}
?>
</div>

</div>

<?php endif; ?>


</div>

<?php endif; ?>