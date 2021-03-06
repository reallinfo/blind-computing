<?php
require_once("db.connect.inc.php");

/**
 * Generates HTML for resources matching a category. The resources are fetched from the database.
 *
 * @param $category
 * @param string $header
 * @return string
 */
function create_resource_list($category, $header = "List of Resources")
{
    global $db;
    $formatted_header = $header === ""?"":"<h2>" . $header . "</h2>";
    $results = $db->prepare("select title,description,uri,feed,contributor,target from pages where category=? order by id asc;");
    $results->execute([$category]);
    if ($results->rowCount()) {
        $output = $formatted_header . "<nav><ul>";
        while ($row = $results->fetchObject()) {
            $feed_text = $row->feed != NULL ?
              "[<a href='{$row->feed}' title='Direct link to the RSS feed' target='{$row->target}'>RSS</a>]":
              "";
            $output .=
                $row->title === "-" ?
                    '<hr>':
                    "<li> {$row->description} 
                      <a href='{$row->uri}' title='credit: {$row->contributor}' target='{$row->target}'>{$row->title}</a> {$feed_text}.</li>";
        }
        $output .= '</ul></nav>';
    } else {
        $output = $formatted_header . '<p>No resources yet, check back later or <a href="/contributing.php">Submit some content to get it here</a>.</p>';
    }
    return $output;
}

/**
 * Generates the page for the category.
 *
 * @param $title
 * @param $description
 * @param $category
 * @param string $header
 * @return string
 */
function create_category_page($title, $description, $category, $header = "List of Resources")
{
    $output = create_title($title, $description);
    $output .= create_resource_list($category, $header);
    return $output;
}

/**
 * Generates a video widget.
 *
 * Generates a Embedded Youtube player with a description and metadata.
 *
 * @param $id
 * @param string $header
* @param boolean use_peertube
* @return string
 */
function create_video_widget($id, $header = "Video Info")
{
    global $db;
    if ($header === "") {
        $formatted_header = "";
    } else {
        $formatted_header = "<h3>" . $header . "</h3>";
    }
    $results = $db->prepare("select published, description, youtube_id, peertube_id, use_peertube, contributor from videos where id=? limit 1");
    $results->execute([$id]);
    $row = $results->fetchObject();
    if ($results->rowCount()) {
        $output = '<section>';
        if ($row->youtube_id != NULL && $row->use_peertube != true) {
          $output .=
            '<iframe style="float:left;" width="55%" height="360" src="https://www.youtube-nocookie.com/embed/' .
            $row->youtube_id .
            '" frameborder="0" allow="encrypted-media" allowfullscreen="yes">Loading...</iframe>';
        } else if ($row->peertube_id != NULL) {
          $output = $output.
            '<iframe style="float:left;" width="55%" height="360" sandbox="allow-same-origin allow-scripts" src="https://peertube.linuxrocks.online/videos/embed/' .
            $row->peertube_id .
            '" frameborder="0" allowfullscreen>Loading ...</iframe>';
        } else {
          die("Error: No video ID provided in database!");
        }
        $output = $output.'</section>' .
            '<aside class="video-info">' .
            $formatted_header .
            '<table><tr><th>Published on: </th><td><time datetime="' .
            $row->published .
            '">' .
            $row->published .
            '</time></td></tr><tr><th>Contributor: </th><td><a href="/profile/' .
            $row->contributor .
            '" title="View this contributor\'s profile" target="blank"> '.
            $row->contributor .
            '</a></td></tr></table><section>' .
            $row->description .
            '</section></aside>';
    } else {
        $output = '<aside class="video-info"><p>Looks like we don\'t have any info on this video. This probably means this page is currently under construction, or the database is down. <strong>Please stand by!</strong></aside>';
    }
    return $output;
}

/**
 * Generates the page for holding the video widget.
 *
 * @param $title
 * @param $description
 * @param $id
 * @param string $header
* @param boolean use_peertube
 * @return string
 */
function create_video_page($title, $description, $id, $header = "Video Info")
{
    $output = create_title($title, $description);
    $output .= create_video_widget($id, $header);
    return $output;
}

/**
 * Generates the title for the page.
 *
 * @param $title
 * @param $description
 * @return string
 */
function create_title($title, $description)
{
    return "<section id='description'><details open><summary><h2>{$title}</h2></summary>{$description}</details></section>\n";
}

/**
 * Generates the information part of the article. It fetches the information from the database.
 *
 * @param $id
 * @return string
 */
function create_article_info($id)
{
    global $db;
    $results = $db->prepare("select title, published, contributor, editted from articles where id=? limit 1");
    $results->execute([$id]);
    $row = $results->fetchObject();
    if ($results->rowCount()) {
        $output =
            '<section class="article-info"><h2>' .
            $row->title .
            '</h2><table><tr><th>Published on: </th><td><time datetime="' .
            $row->published .
            '">' .
            $row->published .
            '</time></td></tr>';
        if ($row->editted != null) {
            $output .=
                '<tr><th>Editted on: </th><td><time datetime="' .
                $row->editted .
                '">' .
                $row->editted .
                '</time></td></tr>';
        }
        $output .= '<tr><th>Author: </th><td><a href="/profile/' .
            $row->contributor .
            '" title="View this contributor\'s profile" target="blank">'.
            $row->contributor .
            '</a></td></tr></table><hr></section>';
    } else {
        $output = '<aside class="article-info"><p>Looks like we don\'t have any info on this article. This probably means this page is currently under construction, or the database is down. <strong>Please stand by!</strong></aside>';
    }
    return $output;
}

/**
 * Generates HTML for a list of contributors with an optional search term.
 *
 * @param string $searchTerm
 * @return string
 */
function create_contributor_list() {
    global $db;
    $results = $db->prepare("select username, fullName, imguri from contributors;");
    $results->execute([]);
    if($results->rowCount()) {
        $output = '<table class="contributor-list dataTable"><thead><tr><th>UserName</th><th>FullName</th></tr></thead><tbody>';
       while($row = $results->fetchObject()) {
            $output = $output.
                '<tr class="table-row-hilight"><td><a href="/profile/'.
                $row->username.
                '"> ';
            if($row->imguri != NULL) {
                $output = $output.'<img src="'.
                    $row->imguri.
                    '" class="profile-img-small" aria-label="profile picture for '.
                    $row->username.
                    '."> ';
            }
                $output = $output.
                $row->username.
                '</a></td><td>'.
                $row->fullName.
                '</td></tr>';
        }
        $output = $output.'</tbody></table>';
    }
    return $output;
}
?>
