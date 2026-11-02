<?php
/**
 * Configuración de Base de Datos - A-AM
 * Apunta estas credenciales a tu base de datos REAL de Snipe-IT en producción.
 */
return [
    'db_host' => '127.0.0.1',
    'db_name' => 'snipeit_a_am', // Cambiar al nombre de la BD real de Snipe-IT
    'db_user' => 'root',         // Usuario de producción
    'db_pass' => '',             // Contraseña de producción
    
    // Parámetros de Simulación de Gravedad
    'gravity_settings' => [
        'base_mass_multiplier' => 0.01,
        'default_criticality' => 5
    ]
];
