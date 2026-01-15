# Symfony 7

## Cosmic Coding with Symfony 7

### 01. Inicjalizacja aplikacji symfony

Symfony to kolekcja ponad 200 bibliotek php.

Plik binarny symfony to narzędzie, które pomaga między innymi w zarządzaniu projektami Symfony, uruchamianiu lokalnego serwera WWW i wdrażaniu aplikacji.
Polecenie *symfony new starshop* inicjuje nowy projekt Symfony w katalogu o nazwie starshop, ustanawiając podstawową strukturę projektu.

### 02. Zapoznanie z wymaganiami nowego projektu

Apliakcja działa na porcie 8080, jest odpalana z docker-compose up.

Paczki powiązane z symfony znajdują sie w composer.json, ich zainstalowane instancje są w katalogu /vendor.
Katalog /vendor nie jest komitowany do gita.

W projekcie znajdują się dwa, bardzo ważne katalogi: /config oraz /src.
/config zawiera konfiguracje projektu (o tym póżniej).
/src zawiera kod aplikacji.
W katalogu /bin jest plik console (poznam później)
W katalogu /public są zamieszczane assety, które mają być udostępnione publicznie jak obrazki, plik index.php itd.
index.php jest kontrolerem frontu - zarządza requestami.
Katalog /var - również ignorowany przez gita - przechowuje cache i logi aplikacji.

Podczas kursu jest wykorzystywany phpStorm, ja będę używał VSCode.

### 03. Ścieżki, kontrolery i responsy

Routa (ścieżka) - to adres URL do strony
Kontroler odpowiada za renerowanie strony.

Kontroler musi zawierać namespace oraz rozszerzenie .php.
Kontroler zwraca obiekt Response.
Gdy używa sie jakiejś klasy, konieczne jest zadeklarowanie dyrektywy "use".
Jeśli chcę dodać Routę, powinienem użyć #[Route('/')]

```php
  #[Route('/')]
  public function homepage(): Response    
  {
    return new Response(
      '<html><body>Welcome to Starshop!</body></html>',
      Response::HTTP_OK,
      ['Content-Type' => 'text/html']
    );
  }
```

### 04. Magiczny symfony flex

Tworzenie nowego projektu za pomocą komendy **symfony new** polega na dwóch krokach: sklonowaniu repo symfony/skeleton - (cały skeletonz zawiera tylko composer.json) oraz na uruchomieniu **composer install**.

Pozostałe 14 plików z 15 plików nowego projektu pochodzi z paczki symfony/flex.

Symfony/flex dodaje aliasy oraz przepisy/recipies (przygotowane szablony plików).

Aliasy polegają na skróceniu nazw paczek symfony np. symfony/http-client można zaistalować za pomocą **composer require http-client**.

[https://github.com/symfony/recipes]

Do projektu dodałem cs-fixer do sprawdzania standardu stylowania kodu php **composer require cs-fixer-shim**

Poprawa stylowania odbyłą się za pomocą komendy **./vendor/bin/php-cs-fixer fix**

### 05. Twig i szablony (templates)

Twig jest biblioteką, dzięki któej można dynamicznie generować treść HTML. Generowanie odpowiedniego kodu HTML jest dzięki metodzie render() w kontrolerze.

### 06. Dziedziczenie w Twig

Dziedziczenie dodaje sie za pomocą dyrektywy **extends** i nadpisując bloki:

```twig
{% extends 'base.html.twig' %}

{% block title %}Starshop: your monopoly-busting option for Starship parts!{% endblock %}

{% block body %}
```

Blok rodzica można rozszerzyć, używając funkcji twig **parent()**:

```twig
{% block title %}{{ parent() }} Starshop: your monopoly-busting option for Starship parts!{% endblock %}
```

### 07. Debugowanie z pomocą Profilera

> composer require debug

Dzięki komendzie **php bin/console** można podejrzeć jakie są dostępne aliasy w symfony.

Komenda **php bin/console debug:router** wskazuje jakie są routy (w tym te z profilera)

Komenda **php bin/console debug:twig** wskazuje dostępne funkcje, filtry, testy, zmienne globalne oraz ścieżki loaderów.

### 08. Tworzenie końcówek API JSON

Samo tworzenie końcówek jest proste, tworzy się kontroler, w nim zwraca response z jsonem:

```php
return $this->json($starships);
```

Co jeśli Chcę przekazać obiekt?

```php
public function __construct(
        private int $id,
        private string $name,
        private string $class,
        private string $captain,
        private string $status,
    ) {
    }
```

Przekazanie właściwości z konstruktora jest niemożliwe, ponieważ są prywatne, trzebaby użyć getterów
Do użycia getterów potrzebny jest serializer: **composer require serializer**

Po zainstalowaniu paczki z serializerem, cała serializacja przebiega w tle, dzięki AbstractController i metodzie sprawdzającej czy istnieje serializer:

```php
protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
{
    if ($this->container->has('serializer')) {
        $json = $this->container->get('serializer')->serialize($data, 'json', array_merge([
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ], $context));
        return new JsonResponse($json, $status, $headers, true);
    }

    return new JsonResponse($data, $status, $headers);
}
```

JSON można zwracać w API za pomocą:

```php
return new Response(json_encode($data))
```

```php
return new JsonResponse($data)
```

```php
return $this->json($data)
```

### 09. Services: podstawa wszystkiego

Serwis w symfony, to obiekt, który potrafi coś robić np. Logger, który ma metodę log().

Listę serwisów można sprawdzić dzięki komendzie **bin/console debug:container**
Aby pobrać listę automatycznie podłączonych serwisów trzeba użyć komendy: **php bin/console debug:autowiring**
Wyszukiwanie serwisów logujących: **php bin/console debug:autowiring log**

Podpięcie serwisu do dowolnego kontrolera może nastąpić przez odanie go jako parametru:

```php
class StarShipApiController extends AbstractController
{
    #[Route('/api/starships')]
    public function getCollection(LoggerInterface $logger): Response
    {
        $logger->info('Starship collection retrieved');
```

Teraz logi można sprawdzić w pliku var/log/dev.log lub w profilerze /_profiler

Wszystkie serwisy są przechowywane w **service container**.

### 10. Tworzenie własnego serwisu

Nowym serwisem będzie StarshipRepository który będzie przechowywał informacje o statkach, przy okazji zostanie zastosowany refaktor.

Serwisu użyłem w kontrolerze, który przekazuje dane do widoku:

```php
class MainController extends AbstractController
{
    #[Route('/')]
    public function homepage(StarshipRepository $starshipRepository): Response
    {
        $ships = $starshipRepository->findAll();
        $myShip = $ships[array_rand($ships)];

        return $this->render('main/homepage.html.twig', [
            'ships' => $ships,
            'myShip' => $myShip,
        ]);
    }
}
```

W twigu:

```twig
<div>
    {% set numberOfStarships = ships|length %}
    
    Browse through {{ numberOfStarships }} starships!
    {% if numberOfStarships > 400 %}
        <p>
            {# Do you think "shiploads" will pass the legal team? #}
            That's a shiploads of ships!
        </p>
    {% endif %}
</div>
```

Serwisy można dodawać na dwa sposoby, jako wstrzyknięcie do kontrolera - dostępne w całej klasie, lub jako wstrzyknięcie jako argument do wybranej metody, bardziej specyficzne.

### 11. Bardziej skomplikowane routy - wymagania, wiele ścieżek, różne metody itd

Dodanie zmiennych zastępuje przez dodanie w spodziewanym URLu zmiennej '/{zmienna}', jesli zmienna ma być liczbą, to można zamieścić regex: '/{id<\d+>}'
Części wspólne routy można wyniesć ponad metody, do poziomu klasy: 

```php
#[Route('/api/starships')]
class StarShipApiController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function getCollection(StarshipRepository $starshipRepository): Response
    {
        $starships = $starshipRepository->findAll();

        return $this->json($starships);
    }
    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function get(int $id, StarshipRepository $starshipRepository): Response
    {
        $starship = $starshipRepository->find($id);

        if (!$starship) {
          throw $this->createNotFoundException('Starship not found');
        }
        return $this->json($starship);
    }
}
```

Dobrą praktyką zamiast zwracania null z backendu jest zwracanie kodów błędów np. 404.

### 12. Generowanie URL

Generowanie URLa odbywa się przez użycie kontrolera dla konkretnej routy oraz odebranie nazwy routy przez szablon twigowy:

```php
class StarshipController extends AbstractController
{
    #[Route('/starships/{id<\d+>}', name: 'app_starship_show')]
    public function show(int $id, StarshipRepository $starshipRepository): Response
    {
        $starship = $starshipRepository->find($id);

        if (!$starship) {
            throw $this->createNotFoundException('Starship not found');
        }

        return $this->render('starship/show.html.twig', [
            'ship' => $starship,
        ]);
    }
}
```

```twig
<tr>
    <th>Name</th>
    <td>
        <a href="{{ path('app_starship_show', { id: myShip.id }) }}">{{ myShip.name }}</a>
    </td>
</tr>
```

W twigu jako pierwszy argument podczas tworzenia linkowania do eleemntu podaje się nazwę routy, jako drugi podaje się parametry, które określono dla danej routy.

### 13. JS z Asset Mapperem

Instalacja komendą **composer require symfony/asset-mapper**
Do tego będzie potrzebna biblioteka **composer require symfony/asset**

Listę podpiętych assetów można wywołać komendą **php bin/console debug:asset**

Konfiguracja asset-mappera jest w **config/packages/asset_mapper.yaml**

Asset mapper ma pomóc w zarządzaniu plikami js i css.

W twigu z assetów można skorzystać za pomocą funcji asset, dostarczoną wraz z **symfony/asset**:

```
<body>
    <img src="{{ asset('images/starshop-logo.png') }}" alt="Starshop Logo">
    {% block body %}{% endblock %}
</body>
```

Assety są automatycznie wersjonowane, co usprawnia ilość zapytań do serwera dzięki cache.

### 14. TailwindCSS

Dodanie tailwindcss w symfony odbywa sie za pomocą:

> composer require symfonycasts/tailwind-bundle
> php bin/console tailwind:init

Uruchomienie builda z watchem odbywa sie za pomocą komendy:

> php bin/console tailwind:build -w

Uruchomienie watcha można zautomatyzować za pomocą pliku binarnego symfony: **.symfony.local.yaml**

```yaml
workers:
    tailwind:
        cmd: ['symfony', 'console', 'tailwind:build', '--watch']
```

Po dodaniu tego wpisu działa przebudowanie po właczeniu serwera symfony: **symfony serve**

### 15. Komponenty twigowe & pętle for

Dodawanie komponentów do pliku w twigu odbywa sie przez dyrektywę include:

```php
{{ include('main/_shipStatusAside.html.twig')}}
```

Pętle mają następującą składnię:

```php
{% for ship in ships %}
{% endfor %}
```

### 16. Enumy w PHP

Tworzenie StarshipStatusEnum z wartościami:

```php
namespace App\Model;

enum StarshipStatusEnum: string
{
    case WAITING = 'waiting';
    case IN_PROGRESS = 'in progress';
    case COMPLETED = 'completed';
}
```

Użycie enum w repository:

```php
return [
  new Starship(
      1,
      'USS LeafyCruiser (NCC-0001)',
      'Garden',
      'Jean-Luc Pickles',
      StarshipStatusEnum::IN_PROGRESS
  ),
  new Starship(
      2,
      'USS VeggieVoyager (NCC-0002)',
      'Botanical',
      'James T. Tomato',
      StarshipStatusEnum::COMPLETED
  ),
  new Starship(
      3,
      'USS Cosmic Cruiser (NCC-0003)',
      'Interstellar',
      'Spock Lettuce',
      StarshipStatusEnum::WAITING
  ),
];
```

Potrzebne jest również typowanie wg. enuma w modelu Starship:

```php
public function __construct(
    private int $id,
    private string $name,
    private string $class,
    private string $captain,
    private StarshipStatusEnum $status,
) {
}

public function getStatus(): StarshipStatusEnum
{
    return $this->status;
}
```

Późniejsze odczytywanie informacji odbywa się za pomocą właściwości value, bo nie można łatwo konwertować enum do stringa:

```php
    <p class="uppercase text-xs text-nowrap">{{ ship.status.value }}</p>
```

### 17. Sprytne metody na modelach & tworzenie dynamicznego designu

Dla czystości kodu można odwołać się do ship.statusString zamiast do ship.status.value. 

```php
public function getStatusString(): string
{
    return $this->status->value;
}
```

Linkowanie do strony pojedynczego statku odbywa się za pomocą odniesienia do nazwy routy i przekazania id:

```php
<a
    class="hover:text-slate-200"
    href="{{ path('app_starship_show', { id: ship.id }) }}"
>{{ ship.name }}</a>
```

Tworzenie dynamicznej ścieżki do obrazków odbywało się przez dodanie metody do modelu Starship z metchem:

```php
public function getStatusImageFilename(): string
{
    return match ($this->status) {
        StarshipStatusEnum::WAITING => 'images/status-waiting.png',
        StarshipStatusEnum::IN_PROGRESS => 'images/status-in-progress.png',
        StarshipStatusEnum::COMPLETED => 'images/status-complete.png',
    };
}
```

### 18. Stimulus: Pisanie zaawansowanego JSa

Dodanie stimulusa:

> composer require symfony/stimulus-bundle

Dodanie stimulusa powoduje dodanie w assetach app.js importującego bootstrap.js, a w bootstrap.js jest import:

```js
import { startStimulusApp } from '@symfony/stimulus-bundle';
const app = startStimulusApp();
```

Stimulus "porozumiewa się" za pomocą kontrolerów assets/controllers/xxx_controller.js

W kontrolerach są metody, które można wywołać przez deklarację na kontenerze atrybutu "data-controller". Jeśli chcę oznaczyć jakiś handler, to powinienem na handlerze oznaczyć atrybut "data-action":

```js
<div class="flex justify-between mt-11 mb-7">
    <button data-action="closeable#close">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 448 512"><!--!Font Awesome Pro 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2024 Fonticons, Inc.--><path fill="#fff" d="M384 96c0-17.7 14.3-32 32-32s32 14.3 32 32V416c0 17.7-14.3 32-32 32s-32-14.3-32-32V96zM9.4 278.6c-12.5-12.5-12.5-32.8 0-45.3l128-128c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3L109.3 224 288 224c17.7 0 32 14.3 32 32s-14.3 32-32 32l-178.7 0 73.4 73.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0l-128-128z"/></svg>
    </button>
</div>
```

W kontrolerze closeable_controller.js powinny znaleźć się metody obsługujące poprawne działanie:

```js
import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    async close() {
        this.element.style.width = '0';
        await this.#waitForAnimation();
        this.element.remove();
    }
    #waitForAnimation() {
        return Promise.all(
            this.element.getAnimations().map((animation) => animation.finished),
        );
    }
}
```

Hashtag "#" w js ma za zadanie wyznaczyć metodę prywatną.

### 19. Turbo - SPA

Dodanie turbo do projektu:

> composer require symfony/ux-turbo

W pliku assets/controllers.json została dodana konfiguracja symfony/ux-turbo:

```json

{
    "controllers": {
        "@symfony/ux-turbo": {
            "turbo-core": {
                "enabled": true,
                "fetch": "eager"
            },
            "mercure-turbo-stream": {
                "enabled": false,
                "fetch": "eager"
            }
        }
    },
    "entrypoints": []
}
```

Teraz dzięki "enabled": true SPA jest dostępne, ponieważ js wykonuje ajax do tej strony zamiast uderzać pod nowy url.

### 20. Maker bundle: generowanie komend

Dodanie biblioteki odbywa sie za pomocą:

> composer require symfony/maker-bundle --dev

Po zastosowaniu komendy

> symfony console

Widzę listę dostępnych koemnd w konsoli
Aby utworzyć nową komendę muszę wywołać:

> symfony console make:command

Następnie konsola zapyta jak ma się nazywać nowa komenda
Podaje **app:ship-report**

Podczas wysołąnia koemndy

> symfony console app:ship-report

Uruchamiam ją, dostęp kodu komendy jest z poziomu pliku **src/Command/ShipReportCommand.php**
W metodzie execute() mam dostęp do manipulacji argumentami oraz dzieki obiektowi io manipulacji przedstawionymi danymi w konsoli za pomocą stylowania symfony:

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);
    $arg1 = $input->getArgument('arg1');

    if ($arg1) {
        $io->note(sprintf('You passed an argument: %s', $arg1));
    }

    if ($input->getOption('option1')) {
        // ...
    }

    $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

    return Command::SUCCESS;
}
```

Przykładowo dodanie paska progresu:

```php
$io->progressStart(100);
for ($i = 0; $i < 100; ++$i) {
    $io->progressAdvance();
    usleep(10000);
}
$io->progressFinish();
```

## Symfony 7 Fundamentals: Services, Config & Environments

### 01. Setup, Services & the Service Container

Serwisy to kod (obiekt) odpowiadający za wykonywanie okreslonych czynności np. łączenie z bazą, obsługa mailera, logowanie czynności.
Nie każdy obiekt jest serwisem, np. modele nimi nie są, bo przechowują dane bez wykonywania jakichś czynności.

Kontrola jakie mamy serwisy w aplikacji za pomocą komendy:

> symfony console debug:container

Lista serwisów po wykonaniu komendy pochodzi z pliku: **config/bundles.php**

### 02. KnpTimeBundle: Install the Bundle, Get its Service

KnpTimeBundle to paczka, która będzie formatowała daty. Przykłądowo automatycznie obliczy ile czasu od teraz upłynęło do danej daty.

Dodanie biblioteki za pomocą:

> composer require knplabs/knp-time-bundle

Za pomocą komendy można sprawdzić właściwości serwisów powiązanych z obsługą czasu

> symfony console debug:container time

Jeśli chcemy sprawdzić dostępne twigowe metody filtrowania związane z czasem, trzeba skorzystać z komendy:

> symfony console debug:twig

```twig
<div>
    Arrived at: <span class="text-slate-400">{{ ship.arrivedAt|ago }}</span>
</div>
```

Co ciekawe jeśli w debugu jest info o argumentach przekazywanych do funkcji twigowych, to pierwszy argument zawsze jest automatycznie pobierany z wartości któa jest przez pipem "|"

### 03. The HTTP Client Service

Aby sprawdzić jakie mamy klasy powiązane z http, można skorzystać z komendy:

> bin/console debug:autowiring http

Jeśli nie ma potrzebnej biblioteki, to trzeba dodać do projektu np.

> composer require symfony/http-client

Http-client pozwoli nam na skorzystanie z metod takich jak request.

### 04. Cache Service and Cache Pools

Cache pool to coś jak podkatalog dla projektu. Moża wyczyścić cache w poolu, bez czyszczenia całego cache.

Owrapowanie cachem kodu:

```php
$issData = $cache->get('iss_location_data', function (ItemInterface $item) use ($client): array {
    $item->expiresAfter(5);
    $response = $client->request('GET', 'https://api.wheretheiss.at/v1/satellites/25544');
    return $response->toArray();
});
```

Wyświetlenie listy cache:

> bin/console cache:pool:list

Cache można usunąć na 3 sposoby:

- kasując cały cache (symfony console cache:clear)
- kasując konkretny katalog var cache (rm -rf var/cache/prod/pools/stocks/)
- kasując konkretny cache z katalogu (bin/console cache:pool:clear stocks)

### 05. Bundle Config: Configuring the Cache Service

Domyślnie cache w symfony jest przechowywane "na dysku" jako część FilesystemAdapter.
Można zmienić to na pamięć w trakcie żądania dzięki zamieszczeniu w pliku konfiguracujnym config/packages/cache.yaml:

```yaml
framework:
    cache:
        app: cache.adapter.array
```

Czemu cache w pamięci żądania jest przydatne w porównaniu do niekorzystania z cahce? Jeśli podczas jednego żądania w różnych miejscach kontrolera są potrzebne zewnętrzne dane, to nie pobieramy ich za każdym razem, tylko przy pierwszym zapytaniu.

Komenda zwraca aktualną konfigurację dla rozszerzenia (w tym przypadku twig):

> bin/console debug:config twig

Komenda zwraca domyślną konfigurację:

> bin/console config:dump-reference twig

### 06. How autowiring works

autowiring to automatyczne definiowanie zależności serwisów

> bin/console debug:autowiring

Ta komenda pokazuje listę serwisów możliwych do zdefiniowania.
Następna komenda wskazuje listę serwisów i ich ID:

> bin/console debug:container

Jeśli ID serwisu nie jest interfacem lub nazwą klasy, to jego nadpisanie nie jest możliwe.

Dodanie poolu odbywa sie przez **config/packages/cache.yaml**

```yaml
framework:
    cache:
        pools:
            iss_location_pool:
                default_lifetime: 5
```

Po wpisaniu bin/console debug:autowiring pojawia się w konsoli:

> Covers most simple to advanced caching needs.
> Symfony\Contracts\Cache\CacheInterface - alias:cache.app
> Symfony\Contracts\Cache\CacheInterface $issLocationPool - target:iss_location_pool

Można użyć $issLocationPool jako miejsca w którym jest pchechowywany cache dla danych z tej grupy:

```php
public function homepage(
    StarshipRepository $starshipRepository,
    HttpClientInterface $client,
    CacheInterface $issLocationPool,
): Response {
[...]
    $issData = $issLocationPool->get('iss_location_data', function () use ($client): array {
        $response = $client->request('GET', 'https://api.wheretheiss.at/v1/satellites/25544');
        return $response->toArray();
    });
[...]
}
```

Pomaga to zachować porządek w projekcie.

### 7. Symfony environments

W pliku .env znajdują się zmienne środowiskowe.
W nich są informacje o środowisku i hash z sekretem:

```env
APP_ENV=
APP_SECRET=
```

Za odczyt .env odpowiada kontroler /public/index.php
/public/index.php nadaje kontekst zmiennych, tworzy instancje App/Kernel.
App/Kernel używa MicroKernelTrait, tam znajdują się informacje o tym jak działają konfiguracje i skąd pochodzą.
Jest jeszcze jeden sposób implemenctacji konfiguracji w symfony: when@{ENV} przykład w config/packages/framework.yaml lub config/packages/monolog.yaml
Określone środowiska mogą korzystać z paczek projektu w config/bundles.php - tam są określone środowiska dla konkretnych bundli.

Aby działała konfiguracja na produkcji można dodać zarówno dyrektywę when@%env% w okpowednim pliku konfiguracyjnym np. cache.yaml, jak i utworzyć nową konfigurację i dodać ją do katalogu config/packages/prod/ - katalog autokmatycznie sczytywany dla produkcji.

### 8. The Prod Environment

Zmiany środowiska odbywa się za pomocą pliku .env: APP_ENV=prod, przy wpisywaniu komendy: APP_ENV=test symfony serve (ma zawsze pierwszeństwo) lub przez utworzenie i nadpisanie pliku .env.local do sterowania zmiennymi lokalnymi

Czyszczenie cache odbywa sie za pomocą:

> bin/console cache:clear

Jeśli chcę wyczyścić cache na konkretnym środowisku trzeba użyć flagi:

> bin/console cache:clear --env=prod

Zamiana adaptera do przechowywania cache następuje w config/packages/cache.yaml

```yml
when@prod:
    framework:
        cache:
            app: cache.adapter.filesystem
```

Zmieniono z array na filesystem.

### 9. More about services

Rejestrowanie serwisów odbywa się w config/services.yaml. Źródło serwisów domyślnie jest ustawione w resource: '../src/', a wyłączone są pliki takie jak DependencyInjection, Encje, Kernel. Te pliki nie są serwisami.

Pod kluczem _defaults są konfiguracje dla wszystkich serwisów: autowire, autoconfigure, itd.
Autowire aktumatycznie wstrzykuje zależności do serwisów, a autoconfigure rejestruje serwisy jako komendy, eventy itd.

Aby sprawdzić listę serwisów uruchamia się komendę:

> bin/console debug:autowiring

Aby uzyskać pełną listę serwisów potrzebna jest komenda:

> bin/console debug:autowiring --all

W config/packages pliki mają tą samą strukturę, różnią się tylko kluczem jak services lub framework.

### 10. Parameters

Aby uzyskać informacje o tym jaki kontener ma podpięty który serwis, potrzebna była komenda:

> bin/console debug:container

Informacje o serwisach nie są jedynymi informacjami przechowywanymi w projektach, kolejne są parametry:

> bin/console debug:container --parameters

Parametry to wartości, które są przypisane w kontenerze, np. wartości z pliku env:

```
kernel.environment    dev
```

W kodzie możemy użyć takiego parametru przez odwołanie się do metody getParameter():

```
$this->getParameter('kernel.project_dir')
```

W pliku config/packages/twig.yaml jest linijka:

```yaml
twig:
    default_path: '%kernel.project_dir%/templates'
```

Jest to odwołanie do parametru kernel.project_dir, a zamknięcie parametru pomiędzy znakami %[parameter name]% jest składnią odwołania do parametru w yaml.

### 11. Non-Autowireable Arguments

W pliku config/services.yaml jest sekcja parameters, w niej można zamieszczać parametry

```yaml
parameters:
    iss_location_cache_ttl: 5
```

Chcąc użyc w kodzie można albo odwołać się do parametru przez getParameter:

```
dd($this->getParameter('iss_location_cache_ttl'));
```

Albo odwołać się do parametru z services przy pomocy Autowire i param:

```
public function homepage(
    #[Autowire(param: 'iss_location_cache_ttl')]
    $issLocationCacheTtl,
): Response {
    dd($issLocationCacheTtl);
}
```

```
#[Autowire(param: 'iss_location_cache_ttl')] może zostać zamienione na #[Autowire('%iss_location_cache_ttl%')]
```

Lub używając bind w services i automatycznie dodając parametr przez defaults:

```yaml
services:
    # default configuration for services in *this* file
    _defaults:
        autowire: true      # Automatically injects dependencies in your services.
        autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.
        bind:
            $issLocationCacheTtl: '%iss_location_cache_ttl%'
```

```
public function homepage(
    $issLocationCacheTtl,
): Response {
    dd($issLocationCacheTtl);
}
```

### 12. Non-Autowireable Services

Symfony nie tworzy aliasu typu klasy dla komend konsolowych, dlatego jesli np. chce się użyć w kodzie twig.command.debug, to trzeba użyć autowire:

```
class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(
        StarshipRepository $starshipRepository,
        HttpClientInterface $client,
        CacheInterface $issLocationPool,
        #[Autowire(service: 'twig.command.debug')]
        DebugCommand $twigDebugCommand,
    ): Response {
        [...]
    }
}
```

Jeśli domyślnie coś nie jest autowireable to można ręcznie dodać do obsługi za pomocą:

```
#[Autowire(service: 'twig.command.debug')]

lub

#[Autowire('@twig.command.debug')]
```

### 13. Environment Variables

Zmienne środowiskowe wywołuje się w plikach .yaml za pomocą składni:

```yaml
parameters:
    iss_location_cache_ttl: '%env(ISS_LOCATION_CACHE_TTL)%'
```

Dodanie int: w env() wywoła wartość int:

```yaml
parameters:
    iss_location_cache_ttl: '%env(int:ISS_LOCATION_CACHE_TTL)%'
```

Zmienną można zdebugować za pomocą:

> bin/console debug:dotenv

Oprócz zmiennych .env można skorzystać z "Symfony secrets", jest do tego sobny kurs.

### 13. Autoconfiguration

Na środowisku dev i test można skorzystać z maker-bundle. Przy wowołaniu kommendy make:

> bin/console make:

Otrzymam podpowiedzi związanę z komendą make.
Na potrzeby zadania skorzystam z komendy:

> bin/console make:twig-extension

Tworzymy nowe rozszerzenie:

```php
<?php
namespace App\Twig\Extension;
use App\Twig\Runtime\AppExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('function_name', [AppExtensionRuntime::class, 'doSomething']),
        ];
    }
}
```

Podstawiamy odpowiednie nazwy pod metody: function_name to nazwa metody, która będzie używana w twig, a doSomething to nazwa metody, która będzi eużywana w ExtensionRuntime, który został utworzony razem z Extension:

```php
<?php
namespace App\Twig\Runtime;
use Twig\Extension\RuntimeExtensionInterface;
class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }
    public function getIssLocationData($value)
    {
        // ...
    }
}
```

Dzieki temu można zrezygnować z wstrzykiwania zmiennych z kontrolera do szablonu i bezpośrednio skorzystać z nich w kazdym szablonie twigowym.

Sztuczka działa dzięki /config/services.yaml i ustawieniu autoconfigure: true.
To trochę uciążliwy feature, bo cały kod odpowiedzialnyz a ładowanie tych rozszerzeń jest uruchamiany na kazdym szablonie.

## Symfony 7 Fundamentals: Doctrine, Symfony 7 & the Database

### 01. Installing Doctrine

Doctrine pozwala na mapowanie obiektów PHP na tabela baz danych.

Instalacja za pomoca komendy:

> composer require doctrine

Komenda instaluje pakiet Flex (symfony/orm-pack) z libkami potrzebnymi doctrine.

### 02. Database Setup & Docker

Dodanie doctrine i postressql do dockera.

Komenda do sprawdzania zmiennych exportowanych przez symfony:

> symfony var:export --multiline

Do korzystania ze zmiennych wprowadzanych do Dockera powinno się korzystać z komendy:

> symfony console

zamiast bin/console, dzięki symfony console można np. użyć w komendzie DATABASE_URL
