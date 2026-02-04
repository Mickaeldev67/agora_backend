### Lancer les test unit 
php bin/phpunit

### lancer mercure 
1. Se placer dans le dossier de mercure 
2. Tapez :
.\mercure.exe run -c .\Caddyfile

### Lancer le server symfony
Symfony serve
PostMan : Message send id (id: 14)

### Télécharger mercure 
https://github.com/dunglas/mercure/releases/tag/v0.21.6
Prendre le windows x86 x64

copier le code suivant dans un fichier Caddyfile :
:3000 {
    log

    encode zstd gzip

    mercure {
        # JWT pour publishers/subscribers
        publisher_jwt "RemplacerCeJWTToken"
        subscriber_jwt "RemplacerCeJWTToken"
        # Dev-friendly options
        cors_origins http://localhost:5173
        publish_origins *
        anonymous
        demo
        subscriptions
    }

    # endpoint santé
    respond /healthz 200
}