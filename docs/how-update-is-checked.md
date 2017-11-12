# 업데이트가 검사되는 방법

## 워드프레스 업데이트 과정 분석

워드프레스의 option 테이블에 플러그인의 업데이트 정보가 담겨 있는 항목이 있습니다. 옵션 이름은 '_site_transient_update_plugins'입니다. 
보통 이 값은 'get_site_transient()', 'set_site_transient()' 함수를 활용하여 값을 읽고 씁니다. 이 옵션의 값으로 많은 양의 데이터가 직렬화되어 저장됩니다. 불러들인 객체는 stdClass 입니다.

업데이트를 체크하는 함수는 'wp_update_plugins()' 함수입니다. 이 함수는 크론에 의해 하루 2회 자동 실행되기도 하고, 업데이트 페이지에 접근할 때 
실행되기도 합니다. 이 함수는 wordpress.org 서버의 특정 URL 주소에 자신이 가지고 있는 플러그인의 정보(get_site_transient()로 가져온 업데이트 정보)를
넘겨 주어 해당 플러그인들 중 어떤 것이 업데이트 가능한지를 알아 옵니다.




## 옵션 값 'site_transient_update_plugins' 분석
이 값은 stdClass 타입이며 다음과 같은 속성을 가지고 있습니다.

| 속성                     | 타입            | 설명                                                                      |
|-------------------------|----------------|---------------------------------------------------------------------------|
| last_checked            | integer        | 타임스탬프 값. 가장 최근 업데이트 시간을 기록.                                    |
| response                | array          | 업데이트 가능한 플러그인의 목록을 기록합니다.                                                     |
| translations            | array          | 업데이트 가능한 번역 목록을 기록합니다.                                          |
| no_update               | array          | 업데이트 항목이 아닌 목록을 기록합니다.                                          |

### response, no_update 항목
response, no_update 연관 배열의 다음과 같은 구조를 가집니다.

각 항목의 키는  플러그인의 메인 파일 (상대) 경로입니다. 예를 들어 wp-content/plugins/debug-bar/debug-bar.php 가 플러그인의 메인 파일이면,
키는 'debug-bar/debug-bar.php' 입니다.

각 항목의 값은 stdClass 타입입니다. 속성은 아래 표를 참고하세요.

| 속성                     | 타입            | 설명                                                                      |
|-------------------------|----------------|---------------------------------------------------------------------------|
| id                      | string         | 이 플러그인의 식별자입니다. wordpress.org에 등록된 플러그인은 보통 'w.org/plugins/{slug}'로 이름지어집니다. |
| slug                    | string         | 이 플러그인의 슬러그입니다. 플러그인의 경로 디렉토리와 일치하는 경우가 많습니다. 예: 'debug-bar/debug-bar.php'의 경우 'debug-bar' |
| plugin                  | string         | 키와 같은 문자열입니다. 예: debug-bar/debug-bar.php                            |
| new_version             | string         | 최신 버전의 문자열.                                                          |
| url                     | string         | wordpress.org에서 찾을 수 있는 플러그인의 소개 페이지 주소.                       |
| package                 | string         | 최신 버전 플러그인의 압축(zip) 파일을 다운로드 받을 수 있는 주소.                    |
| icons                   | array          | 플러그인의 아이콘 아트워크 주소. '1x', '2x', 'default' 키 아래 각 아이콘의 URL을 지정합니다. |
| banners                 | array          | 플러그인 소개 페이지의 배너 이미지 경로입니다. 배너 이미지란 플러그인 소개 페이지 영역 최상단에 출력되는 플러그인을 대표하는 가로로 긴 이미지를 말합니다. '1x', '2x', 'default' 키 아래 각 이미지의 경로를 지정합니다. |
| banners_rtl             | array          | 다국어를 위한 배너 이미지 경로입니다. |




## 워드프레스 업데이트 요청 데이터 분석
POST 전송을 통해 다음 키와 값을 JSON string encode 하여 넘깁니다.

* all: 'true'로 고정되어 있습니다.
* locale: 사이트에서 사용 가능한 언어 목록을 보냅니다. 예 \["ko_KR"\]
* plugins: 플러그인의 업데이트를 위해 플러그인 정보를 보냅니다. 
* translations: 업데이트 가능한 플러그인 번역 목록을 질의하기 위해 설치된 번역 목록을 보냅니다. wp_get_installed_translations( 'plugins' ) 함수 호출로 불러낼 수 있습니다.

### plugins 항목 세부 정보
POST 전송을 위해 보내는 'plugins' 항목은 JSON 인코딩 되어 있으며, 디코딩했을 때 다음 구조를 가집니다.
* plugins: 댁셔너리로 키는 플러그인의 메인 파일 상대 경로, 내용은 플러그인의 헤더 정보. 'get_plugins()' 함수 호출로 얻을 수 있습니다. 
* active: 리스트로 해당 사이트의 활성화된 플러그인의 목록을 보냅니다. 예 \["woocommerce/woocommerce.php, .... \]

### translation 항목 세부 정보
구조의 예
```
{
  "akismet": {
    "ko_KR": {
      "POT-Creation-Date": "",
      "PO-Revision-Date": "2016-02-22 00:06:17+0000",
      "Project-Id-Version": "Plugins - Akismet Anti-Spam - Stable (latest release)",
      "X-Generator": "GlotPress\/2.4.0-alpha"
    }
  },
  "pods": {
    "ko_KR": {
      "POT-Creation-Date": "",
      "PO-Revision-Date": "2016-03-20 15:44:01+0000",
      "Project-Id-Version": "Stable (latest release)",
      "X-Generator": "GlotPress\/2.1.0-alpha"
    }
  }
}
```



## 커스텀 업데이트 전략

### 추가 업데이트 동작 등록
각 클라이언트 워드프레스 설치본에는 클라이언트 사이트의 업데이트 지원 플러그인을 설치합니다. 이 플러그인은 워드프레스 업데이트를 흉내내어
별도의 업데이트 서버에 플러그인의 업데이트 가능 여부를 질의하는 기능을 가지고 있어야 합니다.

질의 내용은 최대한 워드프레스의 업데이트 질의와 유사한 형태로 구성합니다.

### 추가 업데이트 분석 수행
클라이언트가 보내준 플러그인의 목록 중 추가로 업데이트 가능한 목록을 찾아 응답을 보내줍니다.

### 추가 업데이트 등록
클라이언트 측에서 응답을 받아 업데이트 가능 옵션 값을 수정하여 저정합니다.
