# Clean Page

워드프레스 플러그인에서 테마와 완전히 무관한 빈 페이지를 운영하게 도와줌니다.

## 사용법

아래처럼 설정 배열을 입력하거나,

```php
use Bojaghi\CleanPage\CleanPage

new CleanPage([
 /* 설정 배열 */
]);
```

설정을 리턴하는 파일 경로를 입력하세요.

```php
use Bojaghi\CleanPage\CleanPage

new CleanPage('/path/to/configuration');
```

객체는 add_action(), add_filter()의 콜백 함수에서 생성하지 말고 플러그인의 초기화 코드에 그냥 넣어주세요.
왜나면 CleanPage 클래스가 'init' 또는 다른 액션을 사용할 수 있기 때문입니다.

```php
/**
 * Plugin Name: My Plugin 
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

// 올바른 예
$cleanPage = new Bojaghi\CleanPage\CleanPage('/path/to/config');

// 권장하지 않는 예
add_action( 'init', function () {
    $cleanPage = new Bojaghi\CleanPage\CleanPage('/path/to/config');
});
```

## 설정법

아래는 설정 파일의 예시입니다.

```php
if (!defined('ABSPATH')) {
    exit;
}

return [
    [
        'name'      => 'my-name',
        'condition' => function (string $name): bool {},
        'template'  => function (string $name, mixed $body = null) {},
        'before'    => function (string $name) {},
        'body'      => function (string $name) {},
        'after'     => function (string $name) {},
    ],
    /* ... */
    'exit'           => true,
    'priority'       => 9999,
    'show_admin_bar' => false,
];
```

배열 안에 연관배열로 템플릿을 제어합니다. 연관배열은 다음과 같은 키와 값을 가집니다.

- name: 필수. 유일한 문자열을 지정해 주십시오.
- condition: 필수. 콜백 함수를 지정합니다. 이 함수는 템플릿을 사용할지 아닐지를 결정합니다. 불리언을 리턴해야 합니다.  
- template: 옵션. 템플릿을 지정합니다.
  - 'condition' 콜백 함수가 참을 리턴할 경우 이 템플릿을 사용합니다.
  - 생략하면 기본값 `CleanPages::callbackTemplate()`가 사용됩니다. 
  - 이 값이 문자열이면 파일 경로라고 간주하고 인클루드를 시도합니다.
  - 이 값이 콜백 함수면 호출됩니다. 함수 내에서 직접 출력합니다.
    - 콜백 함수는 2개의 파라미터를 가집니다.
    - 첫번째 파라미터는 문자열이며 'name' 값이 주어집니다. 
    - 두번째 파라미터는 콜백 함수이며 'body' 옵션에 지정된 콜백 함수와 일치합니다.
- before: 옵션. 'template' 옵션이 동작하기 전에 호출되는 함수입니다.
- after: 옵션. 'template' 옵션이 동작한 후 호출되는 함수입니다.
- body: 옵션. 기본 템플릿이 사용되는 경우, 이 값이 사용됩니다. body 태그가 열리고 난 후 바로 호출됩니다.

이 배열에 허용되는 키-값은 다음과 같습니다.

- exit: 불리언입니다. 조건에 맞는 항목을 렌더링한 후 바로 종료합니다. 기본값은 true 입니다.
- priority: CleanPages가 사용하는 'template_redirect' 액션 콜백의 우선순위를 지정합니다. 기본값은 9999입니다.
- show_admin_bar: 페이지에서 어드민 바를 노출할지 결정합니다. 기본값은 false 입니다.


## 기본 템플릿의 액션과 필터

기본 템플릿은 `CustomPages::callbackTemplate` 메서드에서 찾을 수 있습니다.
여기서 정의된 액션과 필터는 다음과 같습니다.

- 액션: 모두 name을 첫번째 인자로 받습니다.
  - bojaghi/clean-pages/head/begin: head 태그 시작 부분
  - bojaghi/clean-pages/head/end: head 태그 종료
  - bojaghi/clean-pages/body/begin: body 태그 시작 부분
  - bojaghi/clean-pages/body/end: body 태그 종료 부분
- 필터: 2개의 인자를 받습니다. 첫번째는 필터될 내용, 두번재는 name입니다.
  - bojaghi/clean-pages/head/meta/viewport: meta 태그 viewport 설정 (width=device-width, initial-scale=1)
  - bojaghi/clean-pages/body/class: body 클래스 필터
