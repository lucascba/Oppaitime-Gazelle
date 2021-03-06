<?
// error out on invalid requests (before caching)
if (isset($_GET['details'])) {
  if (in_array($_GET['details'], ['ut','ur'])) {
    $Details = $_GET['details'];
  } else {
    error(404);
  }
} else {
  $Details = 'all';
}

View::show_header('Top 10 Tags');
?>
<div class="thin">
  <div class="header">
    <h2>Top 10 Tags</h2>
    <? Top10View::render_linkbox("tags"); ?>
  </div>

<?

// defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, [10,100,250]) ? $Limit : 10;

if ($Details == 'all' || $Details == 'ut') {
  if (!$TopUsedTags = $Cache->get_value('topusedtag_'.$Limit)) {
    $DB->query("
      SELECT
        t.ID,
        t.Name,
        COUNT(tt.GroupID) AS Uses
      FROM tags AS t
        JOIN torrents_tags AS tt ON tt.TagID=t.ID
      GROUP BY tt.TagID
      ORDER BY Uses DESC
      LIMIT $Limit");
    $TopUsedTags = $DB->to_array();
    $Cache->cache_value('topusedtag_'.$Limit, $TopUsedTags, 3600 * 12);
  }

  generate_tag_table('Most Used Torrent Tags', 'ut', $TopUsedTags, $Limit);
}

if ($Details == 'all' || $Details == 'ur') {
  if (!$TopRequestTags = $Cache->get_value('toprequesttag_'.$Limit)) {
    $DB->query("
      SELECT
        t.ID,
        t.Name,
        COUNT(r.RequestID) AS Uses
      FROM tags AS t
        JOIN requests_tags AS r ON r.TagID=t.ID
      GROUP BY r.TagID
      ORDER BY Uses DESC
      LIMIT $Limit");
    $TopRequestTags = $DB->to_array();
    $Cache->cache_value('toprequesttag_'.$Limit, $TopRequestTags, 3600 * 12);
  }

  generate_tag_table('Most Used Request Tags', 'ur', $TopRequestTags, $Limit, true);
}

echo '</div>';
View::show_footer();
exit;

// generate a table based on data from most recent query to $DB
function generate_tag_table($Caption, $Tag, $Details, $Limit, $RequestsTable = false) {
  if ($RequestsTable) {
    $URLString = 'requests.php?tags=';
  } else {
    $URLString = 'torrents.php?taglist=';
  }
?>
  <h3>Top <?=$Limit.' '.$Caption?>
    <small class="top10_quantity_links">
<?
  switch ($Limit) {
    case 100: ?>
      - <a href="top10.php?type=tags&amp;details=<?=$Tag?>" class="brackets">Top 10</a>
      - <span class="brackets">Top 100</span>
      - <a href="top10.php?type=tags&amp;limit=250&amp;details=<?=$Tag?>" class="brackets">Top 250</a>
    <?  break;
    case 250: ?>
      - <a href="top10.php?type=tags&amp;details=<?=$Tag?>" class="brackets">Top 10</a>
      - <a href="top10.php?type=tags&amp;limit=100&amp;details=<?=$Tag?>" class="brackets">Top 100</a>
      - <span class="brackets">Top 250</span>
    <?  break;
    default: ?>
      - <span class="brackets">Top 10</span>
      - <a href="top10.php?type=tags&amp;limit=100&amp;details=<?=$Tag?>" class="brackets">Top 100</a>
      - <a href="top10.php?type=tags&amp;limit=250&amp;details=<?=$Tag?>" class="brackets">Top 250</a>
<?  } ?>
    </small>
  </h3>
  <table class="border">
  <tr class="colhead">
    <td class="center">Rank</td>
    <td>Tag</td>
    <td class="center">Uses</td>
  </tr>
<?
  // in the unlikely event that query finds 0 rows...
  if (empty($Details)) {
    echo '
    <tr class="row">
      <td colspan="9" class="center">
        Found no tags matching the criteria
      </td>
    </tr>
    </table><br />';
    return;
  }
  $Rank = 0;
  foreach ($Details as $Detail) {
    $Rank++;
    $Split = Tags::get_name_and_class($Detail['Name']);
    $DisplayName = $Split['name'];
    $Class = $Split['class'];

    // print row
?>
  <tr class="row">
    <td class="center"><?=$Rank?></td>
    <td><a class="<?=$Class?>" href="<?=$URLString?><?=$Detail['Name']?>"><?=$DisplayName?></a></td>
    <td class="number_column"><?=number_format($Detail['Uses'])?></td>
  </tr>
<?
  }
  echo '</table><br />';
}
?>
