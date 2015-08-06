<?php
function bettorAdmin(){
    add_action('admin_menu', 'bettor_plugin_setup_menu');    
    add_action('admin_init', 'bettor_options_init');
}
 
function bettor_plugin_setup_menu(){
        add_menu_page( __("Bettor Plugin Setup","BettorPlugin"), __("Bettor Plugin","BettorPlugin"), 'edit_posts', 'bettor-plugin-bets', 'bettor_init' );
        add_submenu_page( 'bettor-plugin-bets', __("Options","BettorPlugin"), __("Options","BettorPlugin"), 'manage_options', 'bettor-plugin-options', 'bettor_options_page' );
        add_submenu_page( 'bettor-plugin-bets', __("Sports","BettorPlugin"), __("Sports","BettorPlugin"), 'manage_options', 'bettor-plugin-sports', 'bettor_sports_page' );
        add_submenu_page( 'bettor-plugin-bets', __("Bookmaker","BettorPlugin"), __("Bookmaker","BettorPlugin"), 'manage_options', 'bettor-plugin-bookmaker', 'bettor_bookmaker_page' );
}
 
function bettor_init(){
    include_once("betfunctions.php");
    echo "<h2>". __("Bettor Plugin Setup - Evaluate Bet","BettorPlugin")."</h2>";
     
    if(filter_input(INPUT_POST, "bettor_save_bet")!=NULL){
        evaluateBets();
    }
    
    $evaluatedBets=getBets("", "not_evaluated");
    
    $select_auswertung="<option value='1'>".__("Back: 100% Win", "BettorPlugin")."</option>
                <option value='2'>".__("Back: 100% Lost", "BettorPlugin")."</option>
                <option value='3'>".__("Back: Cashback", "BettorPlugin")."</option>
                <option value='4'>".__("Back: 50% Cashback - 50% Win", "BettorPlugin")."</option>
                <option value='5'>".__("Back: 50% Cashback - 50% Lost", "BettorPlugin")."</option>
                <option value='6'>".__("Back: 50% Win - 50% Lost", "BettorPlugin")."</option>
                <option value='7'>".__("Lay: 100% Gewinn", "BettorPlugin")."</option>
                <option value='8'>".__("Lay: 100% Lost", "BettorPlugin")."</option>";

    if(count($evaluatedBets)>0){
        echo '<form method="post" action="admin.php?page=bettor-plugin-bets">';
    }
    foreach($evaluatedBets as $bet){
        $categories=bettorGetCategories($bet->id);
        
        echo "<h3>".$bet->datum." ".$bet->liga." ".$bet->spiel." ".$bet->tip."</h3>"
            . "<label class='bettor_block' for='auswertung'>".__("Evaluate", "BettorPlugin").":</label>"
                . "<select id='auswertung' name='auswertung[".$bet->id."]' size='1'>".$select_auswertung."</select>"
            . "<label class='bettor_block' for='odd'>".__("Odd", "BettorPlugin").":</label>"
                . "<input type='text' id='ergebnis' name='odd[".$bet->id."]' value='".$bet->odd."'>"
            . "<label class='bettor_block' for='ergebnis'>".__("Result", "BettorPlugin").":</label>"
                . "<input type='text' id='ergebnis' name='ergebnis[".$bet->id."]' value=''>"
            . "<label class='bettor_block' for='categories'>".__("Categorie", "BettorPlugin").":</label>"
                .$categories    
            ."<label class='bettor_block' for='deletebet'>".__("For delete this bet insert \"Delete\"","BettorPlugin").":</label>"
                . "<input type='text' id='deletebet' name='delete[".$bet->id."]' value=''>"
                . "<input type='hidden' name='id[]' value='".$bet->id."'>"
                . "<input type='hidden' name='idbeitrag[".$bet->id."]' value='".$bet->id_beitrag."'>"
                . "<hr>";
    }

    if(count($evaluatedBets)==0){
        echo "<h3>".__("All bets are evaluated!","BettorPlugin")."</h3>";
    }else{
        echo "<button class='button button-primary button-large' name='bettor_save_bet' value='save' type='submit'>".__("Save","BettorPlugin")."</button></form>";
    }
}

function evaluateBets(){
    $delete=  filter_input(INPUT_POST, "delete", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $result=  filter_input(INPUT_POST, "ergebnis", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $odd=  filter_input(INPUT_POST, "odd", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $evaluate=  filter_input(INPUT_POST, "auswertung", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $ids= filter_input(INPUT_POST, "id", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $category=filter_input(INPUT_POST, "bettor_categories", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $beitrag=filter_input(INPUT_POST, "idbeitrag", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    

    foreach($ids as $id){
        if($delete[$id]===__("Delete","BettorPlugin")){
            deleteBet($id);
        }
        
        if($category[$id]>0){
            bettorChangeCategory($beitrag[$id], $category[$id]);
        }
        
        if($result[$id]!=NULL){
            saveResult($id, $result[$id], $odd[$id], $evaluate[$id]);
        }
    }
}

function deleteBet($id){
    global $wpdb;
    
    $wpdb->update( 
            $wpdb->prefix . "bettor_bet", 
            array( 'loeschen' => 1 ), 
                array( 'id' => $id )
            );
}

function saveResult($id, $result, $odd, $evaluate){
    global $wpdb;
    
    $wpdb->update( 
        $wpdb->prefix . "bettor_bet", 
        array( 
                'ergebnis'  =>  $result,
                'wertung'   =>  $evaluate,
                'odd'       =>  $odd,
                'ausgewertet' => date('Y-m-d H:i:s')
        ), 
        array( 'id' => $id )
    );
}


function bettor_sports_page() {
    include_once("betfunctions.php");
    wp_enqueue_media();
    
    if(filter_input(INPUT_POST, "bettor_save_sports")!=NULL){
        saveSports();
    }
    
    $id=0;
    $sports=  getSports();
    
    echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
    echo '<h2>'.__("Admin Sports","BettorPlugin").'</h2>';
    echo '<form method="post" action="admin.php?page=bettor-plugin-sports">';
    echo "<button class='button button-primary button-large' name='bettor_save_sports' value='save' type='submit'>".__("Save","BettorPlugin")."</button>";
    foreach($sports as $sport){
        $image=wp_get_attachment_image_src( $sport->picture, 'thumbnail' );
        
        echo "<div class='div_80height'>"
        . "<span class='span_textalignvertical'><label for='sports'>".__("Sports","BettorPlugin").":</label>"
            . "<input type='text' id='sports' name='sports[".$id."]' value='".$sport->sports."'>"
        . "<button type='button' class='button button-primary upload_image_button' id='bsportspicture_".$id."' name='sportspicture_b[".$id."]' value='".$sport->picture."'>".__('Choose Picture',"BettorPlugin")."</button></span>"
        . "<img style='position:absolute;' width='80' height='80' id='imageBox_".$id."' src='".$image[0]."'>"
        . "<input type='hidden' name='idsports[".$id."]' id='idsports".$id."' value='".$sport->id."'>"        
        . "<input type='hidden' name='sportspicture[".$id."]' id='picture_".$id."' value='".$sport->picture."'>"        
        ."</div>";
        $id++;
    }
    
    echo "<div class='div_80height'>"
            . "<span class='span_textalignvertical'><label for='sports'>".__("Sports","BettorPlugin").":</label>"
                . "<input type='text' id='sports' name='sports[".$id."]' value=''>"
            . "<button type='button' class='button button-primary upload_image_button' id='bsportspicture_".$id."' name='sportspicture_b[".$id."]' value=''>".__('Choose Picture',"BettorPlugin")."</button></span>"
            . "<img style='position:absolute;' width='80' height='80' id='imageBox_".$id."'>"
            . "<input type='hidden' name='idsports[".$id."]' id='idsports".$id."' value=''>"   
            . "<input type='hidden' name='sportspicture[".$id."]' id='picture_".$id."' value=''>"  
            ."</div>";

    echo "</form>";
    echo '</div>';
}

function saveSports(){
    $sports=  filter_input(INPUT_POST, "sports", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $picture=  filter_input(INPUT_POST, "sportspicture", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $id=  filter_input(INPUT_POST, "idsports", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    
    if(count($sports)==0){
        return false;
    }
    foreach ($sports as $key => $value) {
        if($value!=='' && isset($picture[$key]) && $picture[$key]!==''){
            global $wpdb;
            
            if($id == NULL || $id==""){
                $sql="INSERT INTO ".$wpdb->prefix . "bettor_sports"." (sports, picture) VALUES (%s,%d) ON DUPLICATE KEY UPDATE sports=VALUES(sports), picture=VALUES(picture);";
                $wpdb->query($wpdb->prepare(
                    $sql,
                    $value,
                    $picture[$key]
                ));
            }else{
                $sql="INSERT INTO ".$wpdb->prefix . "bettor_sports"." (id, sports, picture) VALUES (%d, %s,%d) ON DUPLICATE KEY UPDATE sports=VALUES(sports), picture=VALUES(picture);";
                $wpdb->query($wpdb->prepare(
                    $sql,
                    $id[$key],
                    $value,
                    $picture[$key]
                ));
            }
        }
    }
}

function bettor_bookmaker_page(){
    include_once("betfunctions.php");
    wp_enqueue_media();
    $id=0;
    echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
    echo '<h2>'.__("Admin Bookmaker","BettorPlugin").'</h2>';
    echo "<p>".__("Please fill in the fields. If you use the special link field the link and picture field will be ignored!","BettorPlugin")."</p>";
    
    saveBookmaker();
    
    $bookmakers=  getBookmakers();
    
    echo '<form method="post" action="admin.php?page=bettor-plugin-bookmaker">';
    echo "<button class='button button-primary button-large' name='bettor_save_sports' value='save' type='submit'>".__("Save","BettorPlugin")."</button>";
    foreach($bookmakers as $bookmaker){
        $image="";
        if($bookmaker->picture!="" && wp_attachment_is_image($bookmaker->picture)){
            $image=wp_get_attachment_image_src( $bookmaker->picture, 'thumbnail' );
            $image="src='".$image[0]."'";
        }
              
    echo "<div class='div_100_p_height'>"
            . "<span class='span_textalignvertical'><label for='bookmaker'>".__("Bookmaker","BettorPlugin").":</label>"
                . "<input type='text' id='bookmaker' name='bookmaker[".$id."]' value='".$bookmaker->bookmaker."'>"
            . "<label for='bookmaker_link'>".__("Bookmaker Link","BettorPlugin").":</label>"
                . "<input type='text' id='bookmaker_link' name='bookmaker_link[".$id."]' value='".$bookmaker->link."'>"
            . "<button type='button' class='button button-primary upload_image_button' id='bbookmakerpicture_".$id."' name='bookmakerpicture_b[".$id."]' value=''>".__('Choose Picture',"BettorPlugin")."</button></span>"
            . "<img style='position:absolute;' width='80' height='80' id='imageBox_".$id."' ".$image.">"
            . "<br><label for='bookmaker_speciallink'>".__("Bookmaker Special Link (with JS as exampel)","BettorPlugin").":</label>"
                . "<textarea cols='50' rows='5' class='bettor_aligntop' id='bookmaker_speciallink' name='bookmaker_speciallink[".$id."]'>".htmlentities($bookmaker->speciallink)."</textarea>"
            . "<input type='hidden' name='idbookmaker[".$id."]' id='idbookmaker".$id."' value='".$bookmaker->id."'>"   
            . "<input type='hidden' name='bookmakerpicture[".$id."]' id='picture_".$id."' value='".$bookmaker->picture."'>"  
            ."</div><hr>";
        $id++;
    }
    
    echo "<div class='div_100_p_height'>"
            . "<span class='span_textalignvertical'><label for='bookmaker'>".__("Bookmaker","BettorPlugin").":</label>"
                . "<input type='text' id='bookmaker' name='bookmaker[".$id."]' value=''>"
            . "<label for='bookmaker_link'>".__("Bookmaker Link","BettorPlugin").":</label>"
                . "<input type='text' id='bookmaker_link' name='bookmaker_link[".$id."]' value=''>"
            . "<button type='button' class='button button-primary upload_image_button' id='bbookmakerpicture_".$id."' name='bookmakerpicture_b[".$id."]' value=''>".__('Choose Picture',"BettorPlugin")."</button></span>"
            . "<img style='position:absolute;' width='80' height='80' id='imageBox_".$id."'>"
            . "<br><label for='bookmaker_speciallink'>".__("Bookmaker Special Link (with JS as exampel)","BettorPlugin").":</label>"
                . "<textarea cols='50' rows='5' class='bettor_aligntop' id='bookmaker_speciallink' name='bookmaker_speciallink[".$id."]' value=''></textarea>"
            . "<input type='hidden' name='idbookmaker[".$id."]' id='idbookmaker".$id."' value=''>"   
            . "<input type='hidden' name='bookmakerpicture[".$id."]' id='picture_".$id."' value=''>"  
            ."</div>";

    echo "</form>";
    
    echo "</div>";
}


function saveBookmaker(){
    $bookmakers=  filter_input(INPUT_POST, "bookmaker", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $bookmaker_link=  filter_input(INPUT_POST, "bookmaker_link", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $bookmaker_speciallink=  filter_input(INPUT_POST, "bookmaker_speciallink", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $picture=  filter_input(INPUT_POST, "bookmakerpicture", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $key=  filter_input(INPUT_POST, "idbookmaker", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    
    if(count($bookmakers)==0){
        return false;
    }
    foreach($bookmakers as $id => $bookmaker){        
        if($bookmaker!=='' && ((isset($picture[$id]) && $picture[$id]!=='') || strlen($bookmaker_speciallink[$id])>0)){
            global $wpdb;
            
            if($key[$id] == NULL || $key[$id]==""){
                $sql="INSERT INTO ".$wpdb->prefix . "bettor_bookmaker"." (bookmaker, picture, link, speciallink) VALUES (%s, %d, %s, %s) "
                        . "ON DUPLICATE KEY UPDATE bookmaker=VALUES(bookmaker), picture=VALUES(picture), link=VALUES(link), speciallink=VALUES(speciallink);";
                $wpdb->query($wpdb->prepare(
                    $sql,
                    $bookmaker,
                    $picture[$id],
                    $bookmaker_link[$id],
                    $bookmaker_speciallink[$id]    
                ));
            }else{
                $sql="INSERT INTO ".$wpdb->prefix . "bettor_bookmaker"." (id, bookmaker, picture, link, speciallink) VALUES (%d, %s, %d, %s, %s) "
                        . "ON DUPLICATE KEY UPDATE bookmaker=VALUES(bookmaker), picture=VALUES(picture), link=VALUES(link), speciallink=VALUES(speciallink);";
                $wpdb->query($wpdb->prepare(
                    $sql,
                    $key[$id],    
                    $bookmaker,
                    $picture[$id],
                    $bookmaker_link[$id],
                    $bookmaker_speciallink[$id]
                ));
            }
        }
    }
}

function bettorGetCategories($betid){
    $categories=get_categories(array('hide_empty'=> 0));
    
    $ret= "<select name='bettor_categories[$betid]'>";
    $ret.= "<option value='-1'>".__('Please select',"BettorPlugin")."</option>";
    foreach($categories as $cat){
        $ret.= "<option value='".$cat->cat_ID."'>".$cat->name."</option>";
    }
    $ret.= "</select>";
        
    return $ret;
}

function bettorChangeCategory($post_id, $category){
    if($post_id<=0){
        return false;
    }
    
    if($category<=0){
        return false;
    }
    
    wp_set_post_categories( $post_id, $category, false );
}

function bettor_options_page(){
    if(isset($_GET['settings-updated'])){
        $test=$_GET['settings-updated'];
        echo '<div class="updated"><p><strong>'.__('Settings saved.' ).'</strong></p></div>';
    }

    echo '<div class="wrap">';
    echo "<h2>".__('Options',"BettorPlugin")."</h2>";
    echo "<form method='post' action='options.php'>";
    
    settings_fields('bettor_options');
    do_settings_sections('bettor_options_page');
    submit_button();
    echo "<button class='button button-primary button-large' name='bettor_options[generate_statistic_page]' value='generate_statistic_page' type='submit'>".__("Generate Statisc Page","BettorPlugin")."</button>";
    echo "</form>
    </div>";
}

function bettor_section_text() {
    echo '<p>'.__("Change here all the options for the bettor plugin. Beware If you disable the view of the bets you hace to use use the shortcode [bettor_bet] in the post.","BettorPlugin").'</p>';
}

function bettor_setting_color_bet() {
    $options = get_option('bettor_options');
    echo "<input id='bettor_color_bet' name='bettor_options[color_bet]' size='10' type='text' value='{$options['color_bet']}' />";
}

function bettor_settings_picturesize(){
    $options = get_option('bettor_options');
    $checked="";
    if(isset($options["picturesize"])){
        $opt_size=$options["picturesize"];
    }else{
        $opt_size='thumbnail';
    }
    if($opt_size=='full'){
        $checked="selected";
    }
    

    echo "<select name='bettor_options[picturesize]'>";
    echo "<option ".$checked." value='full'>full</option>";

    foreach(get_intermediate_image_sizes() as $size){
        $checked="";
        if($opt_size==$size){
            $checked="selected";
        }
        echo "<option ".$checked." value='".$size."'>".$size."</option>";
    }
    echo "</select>";
}

function bettor_setting_picture() {
    $options = get_option('bettor_options');
    $checked="";
    if($options['picture']==1){
        $checked="checked";
    }
    echo '<input id="bettor_picture" type="checkbox" name="bettor_options[picture]" value="showpicture" '.$checked.'>';
}

function better_setting_showBets(){
    $options = get_option('bettor_options');
    $checked['top']="";
    $checked['bottom']="";
    $checked['no']="";
    if(isset($options['showBets'])){
        $checked[$options['showBets']]="checked";
    }else{
        $checked['top']="checked";
    }
    echo ' <fieldset> 
                <input type="radio" '.$checked['top'].' id="top" name="bettor_options[showBets]" value="top"><label for="top">'.__("Top","BettorPlugin").'</label>
                <input type="radio" '.$checked['bottom'].' id="bottom" name="bettor_options[showBets]" value="bottom"><label for="bottom">'.__("Bottom","BettorPlugin").'</label>
                <input type="radio" '.$checked['no'].' id="no" name="bettor_options[showBets]" value="no"><label for="no">'.__("Nowhere","BettorPlugin").'</label> 
             </fieldset>';
}

function better_setting_pictureAlign(){
    $options = get_option('bettor_options');
    $checked['left']="";
    $checked['right']="";
    $checked['center']="";
    if(isset($options['pictureAlign'])){
        $checked[$options['pictureAlign']]="checked";
    }else{
        $checked['left']="checked";
    }
    echo ' <fieldset> 
                <input type="radio" '.$checked['left'].' id="top" name="bettor_options[pictureAlign]" value="left"><label for="top">'.__("left","BettorPlugin").'</label>
                <input type="radio" '.$checked['right'].' id="bottom" name="bettor_options[pictureAlign]" value="right"><label for="bottom">'.__("right","BettorPlugin").'</label>
                <input type="radio" '.$checked['center'].' id="no" name="bettor_options[pictureAlign]" value="center"><label for="no">'.__("center","BettorPlugin").'</label> 
             </fieldset>';
}

function bettor_setting_currency() {
    $options = get_option('bettor_options');
    echo "<input id='bettor_currency' name='bettor_options[currency]' size='10' type='text' value='{$options['currency']}' />";
}

function bettor_setting_unit() {
    $options = get_option('bettor_options');
    if(isset($options['currency'])){
        $currency=$options['currency'];
    }else{
        $currency="€";
    }
    echo "<input id='bettor_unit' name='bettor_options[unit]' size='10' type='text' value='{$options['unit']}' />".$currency;
}

function bettor_options_init(){
    register_setting( 'bettor_options', 'bettor_options', 'bettor_options_validate' );
    add_settings_section('bettor_main', __('Main Settings for Bettor Plugin',"BettorPlugin"), 'bettor_section_text', 'bettor_options_page');
    add_settings_field('bettor_color_bet', __("Change color of tips (ex. Hex code:#1e77e0)","BettorPlugin"), 'bettor_setting_color_bet', 'bettor_options_page','bettor_main');
    add_settings_field('bettor_picture', __("Show picture auf sports?","BettorPlugin"), 'bettor_setting_picture', 'bettor_options_page','bettor_main');
    add_settings_field('bettor_pictursize', __("Size of the sports picture","BettorPlugin"), 'bettor_settings_picturesize', 'bettor_options_page','bettor_main');
    add_settings_field('bettor_picturalign', __("Align of the sports picture","BettorPlugin"), 'better_setting_pictureAlign', 'bettor_options_page','bettor_main');
    add_settings_field('bettor_showBets', __("Where to show Bets?","BettorPlugin"), 'better_setting_showBets', 'bettor_options_page','bettor_main');
    add_settings_field('bettor_currency', __("Currency","BettorPlugin"), 'bettor_setting_currency', 'bettor_options_page','bettor_main');
    add_settings_field('bettor_units', __("Value of a Unit","BettorPlugin"), 'bettor_setting_unit', 'bettor_options_page','bettor_main');
}

function bettor_options_validate($input) {    
    $options = get_option('bettor_options');
    if(isset($input['generate_statistic_page'])){
        bettorGenerateStatisticPage();
    }
    
    if(isset($input['color_bet']) && $input['color_bet']!=="" && preg_match('/^#[0-9a-fA-F]{6}$/i', $input['color_bet'])){           
        $options['color_bet'] = trim($input['color_bet']);
    }

    if(isset($input['picture']) && $input['picture']=="showpicture"){           
        $options['picture'] = 1;
    }else{
        $options['picture'] = 0;
    }

    if(isset($input['pictureAlign']) && in_array($input['pictureAlign'],array("left","right","center"))){           
        $options['pictureAlign'] = $input['pictureAlign'];
    }

    if(isset($input['picturesize']) && (in_array($input['picturesize'],get_intermediate_image_sizes()) || $input['pictureAlign']=='full')){           
        $options['picturesize'] = $input['picturesize'];
    }

    if(isset($input['showBets']) && in_array($input['showBets'], array("top", "bottom", "no"))){
        $options['showBets']=$input['showBets'];
    }

    if(isset($input['unit']) && $input['unit']!=="" && preg_match('/^\d{1,8}([\.,]\d{2})?$/', $input['unit'])){           
        $options['unit'] = trim($input['unit']);
    }

    if(isset($input['currency']) && $input['currency']!==""){           
        $options['currency'] = trim($input['currency']);
    }
        

    return $options;
}

function bettorGenerateStatisticPage(){
    write_log("Seite generiert!");
        // Create post object
    $my_post = array(
      'post_title'    => __("Bet Statistic","BettorPlugin"),
      'post_content'  => '[bettor_statistic_graph][bettor_statistic_block][bettor_statistic_table][bettor_statistic_years]',
      'post_status'   => 'publish',
      'post_author'   => get_current_user_id(),
      'post_type'     => 'page',
    );

    // Insert the post into the database
    wp_insert_post( $my_post, '' );
}