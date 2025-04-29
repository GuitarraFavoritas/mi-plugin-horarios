// webpack.config.js
const path = require('path');

module.exports = {
    // Modo: 'development' para depuración más fácil, 'production' para archivos optimizados
    mode: 'development', // Cambia a 'production' antes de desplegar
    // Punto(s) de entrada: Archivo(s) JS principales donde empiezan tus importaciones
    entry: {
        admin: './assets/src/js/admin/main.js', // Nuestro archivo JS principal para el admin
        // public: './assets/src/js/public/main.js', // Podrías añadir uno para el frontend aquí
    },
    // Salida: Dónde y cómo se llamarán los archivos compilados (bundles)
    output: {
        // Usar [name] para generar archivos con el nombre de la entrada (ej. admin.js, public.js)
        filename: '[name].bundle.js',
        // Ruta absoluta donde se guardarán los bundles
        path: path.resolve(__dirname, 'assets/dist/js'), // Usaremos una carpeta 'dist'
        clean: true, // Limpiar la carpeta 'dist' antes de cada build
    },
    // Reglas para procesar diferentes tipos de archivos (ej. usar Babel para .js)
    module: {
        rules: [
            {
                test: /\.js$/, // Aplicar a todos los archivos .js
                exclude: /node_modules/, // No transpilar código de terceros
                use: {
                    loader: 'babel-loader', // Usar Babel
                    options: {
                        presets: ['@babel/preset-env'], // Usar preset estándar para compatibilidad
                    },
                },
            },
        ],
    },
    // Opciones adicionales
    devtool: 'source-map', // Generar source maps para facilitar la depuración en el navegador
    // Externals: Indicar a Webpack que ciertas dependencias (como jQuery)
    // ya estarán disponibles globalmente en el entorno de WordPress y no debe incluirlas en el bundle.
    externals: {
        jquery: 'jQuery', // Le dice a Webpack: cuando vea `import $ from 'jquery'`, use la variable global `jQuery`.
    },
};