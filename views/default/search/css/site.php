<?php
/**
 * Elasticsearch CSS
 */
?>

/**********************************
Search plugin
***********************************/
.elgg-search-header {
    bottom: 5px;
    height: 23px;
    position: absolute;
    right: 0;
}

.elgg-search input[type=text] {
    width: 230px;
}

.elgg-search input[type=submit] {
    display: none;
}

.elgg-search input[type=text] {
    -webkit-border-radius: 10px;
    -moz-border-radius: 10px;
    border-radius: 10px;
    border: 1px solid #71b9f7;
    color: white;
    font-size: 12px;
    font-weight: bold;
    padding: 2px 4px 2px 26px;
    background: transparent url(<?php echo elgg_get_site_url(); ?>_graphics/elgg_sprites.png) no-repeat 2px -934px;
}

.elgg-search input[type=text]:focus, .elgg-search input[type=text]:active {
    background-color: white;
    background-position: 2px -916px;
    border: 1px solid white;
    color: #0054A7;
}

.search-list li {
    padding: 5px 0 0;
}
.search-heading-category {
    margin-top: 20px;
    color: #666666;
}

.search-highlight {
    background-color: #bbdaf7;
}
.search-highlight-color1 {
    background-color: #bbdaf7;
}
.search-highlight-color2 {
    background-color: #A0FFFF;
}
.search-highlight-color3 {
    background-color: #FDFFC3;
}
.search-highlight-color4 {
    background-color: #ccc;
}
.search-highlight-color5 {
    background-color: #4690d6;
}

/******************************
Search advanced plugin
*******************************/

.search-advanced-type-selection {
    display: inline-block;
    position: relative;
}

.search-advanced-type-selection-dropdown {
    display: none;
    position: absolute;
    top: 18px;
    right: -4px;
    background: white;
    border: 1px solid #71B9F7;
    padding: 10px 20px 10px 10px;
    z-index: 10;

    text-align: right;

    -webkit-box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.5);
    -moz-box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.5);
    box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.5);
}

.search-advanced-type-selection > li {
    display: inline;
    position: relative;
    height: 20px;
}

.search-advanced-type-selection > li > a {
    padding: 0 5px;
    margin-right: -4px;
    display: inline-block;
    height: 20px;
    line-height: 20px;
    background: #71B9F7;
    color: white;
    font-weight: bold;
}

.search-advanced-type-selection > li > a:after {
    content: "\25BC";
    padding: 0 0 0 2px;
}

.search-advanced-type-selection > li > a:hover {
    text-decoration: none;
}

.search-advanced-type-selection a {
    color: #333;
    cursor: pointer;
}

.search-advanced-type-selection-dropdown a {
    padding: 5px;
    white-space: nowrap;
    display: inline-block;
    width: 100%;
}

.search-advanced-type-selection-dropdown a:hover {
    background: #4690D6;
    color: white;
    text-decoration: none;
}

form.elgg-search {
    border: 1px solid #71B9F7;
    height: 19px;
}

.elgg-search input[type="text"].search-input {
    background-position: 210px -934px;
    padding: 0px 26px 0px 4px;
    height: 19px;
    border: none;
    border-left: 1px solid #71B9F7;
    -webkit-border-radius: 0px;
    -moz-border-radius: 0px;
    border-radius: 0px;
    position: relative;
    vertical-align: top;
}