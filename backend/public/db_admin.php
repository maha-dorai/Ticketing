<?php
function adminer_object() {
    class AdminerSoftware extends Adminer {
        function login($login, $password) {
            // On autorise tout
            return true;
        }
        function credentials() {
            // Force des identifiants bidons pour éviter l'erreur "sans mot de passe"
            return array('localhost', 'admin', 'password');
        }
        function database() {
            return "../database/database.sqlite";
        }
    }
    return new AdminerSoftware;
}

include "./adminer.php";
