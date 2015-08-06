<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
function activate_function(){
    global $wpdb;
    $table_name = $wpdb->prefix . "bettor_bet";
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE ".$table_name." (
    id int(11) NOT NULL AUTO_INCREMENT,
    id_beitrag int(11) NOT NULL,
    liga varchar(160) NOT NULL,
    sportart INT NOT NULL,
    spiel varchar(160) NOT NULL,
    anstoss datetime NOT NULL,
    tip varchar(160) NOT NULL,
    odd float NOT NULL,
    buchmacher int(11) NOT NULL,
    einsatz float NOT NULL,
    ergebnis varchar(160) DEFAULT NULL,
    wertung tinyint(4) DEFAULT NULL,
    eingestellt datetime DEFAULT NULL,
    ausgewertet datetime DEFAULT NULL,
    kostenlos tinyint(1) NOT NULL,
    gruppe varchar(160) NOT NULL,
    loeschen tinyint(1) NOT NULL,
    post_status varchar(160) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY `unique_tip` (`id_beitrag`,`liga`,`spiel`,`tip`)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
    $table_name = $wpdb->prefix . "bettor_sports";
    
    $sql = "CREATE TABLE ".$table_name." (
    id int(11) NOT NULL AUTO_INCREMENT,
    sports varchar(160) NOT NULL,
    picture INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY `unique_sports` (`sports`)
    ) $charset_collate;";
    
    dbDelta( $sql );
    
    $table_name = $wpdb->prefix . "bettor_bookmaker";
    
    $sql = "CREATE TABLE ".$table_name." (
    id int(11) NOT NULL AUTO_INCREMENT,
    bookmaker varchar(160) NOT NULL,
    picture INT,
    link varchar(250),
    speciallink text,
    PRIMARY KEY (id),
    UNIQUE KEY `unique_bookmaker` (`bookmaker`)
    ) $charset_collate;";
    
    dbDelta( $sql );
}