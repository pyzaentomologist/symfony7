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
