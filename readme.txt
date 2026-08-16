=== NalApps Easy SMTP ===
Contributors: eoingtilab
Tags: smtp, email, mail, phpmailer, deliverability
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress 메일 발송을 위한 안전하고 간단한 SMTP/API 설정, 진단, 시험 발송 도구입니다. 무료로 제공되며 라이선스 등록이 필요하지 않습니다.

== Description ==

NalApps Easy SMTP는 WordPress의 기본 메일 발송(wp_mail)이 신뢰할 수 있는
경로로 전달되도록 연결 정보를 등록하고, 저장된 정보로 실제 테스트 메일을
발송해 발송 상태를 즉시 확인할 수 있는 무료 플러그인입니다.

= 주요 기능 =

* Brevo / Mailgun / SendGrid 중 하나를 선택하면 접속 정보가 자동으로 채워지는 빠른 설정(로고 선택 또는 드롭다운)
* 각 서비스 가입 페이지로 바로 이동하는 바로가기 링크
* SMTP 계정(호스트/포트/사용자명/비밀번호) 또는 API 키 중 원하는 연결 방식 선택
* 위 3개 서비스 외의 SMTP 서버도 직접 입력으로 자유롭게 설정 가능
* 저장된 설정으로 즉시 테스트 메일 발송
* SMTP 비밀번호와 API 키는 이 사이트에서만 해독 가능하도록 암호화되어 저장(사이트 간 이동 불가)
* 무료 다운로드형 제품: 시리얼키 등록/활성화 절차 없이 항상 활성 상태로 모든 기능 사용 가능
* GitHub Release 기준 업데이트 확인 및 실행형 업데이트
* 업데이트/롤백 직전 자동 코드·설정 백업, 검증된 이전 Release로 버전 롤백
* 설정 백업(JSON) 내보내기/가져오기(비밀번호·API 키 제외)
* 비밀정보를 노출하지 않는 읽기 전용 시스템 정보 화면
* 플러그인 제거 시 기본값은 설정 보존, 명시적으로 켠 경우에만 전체 삭제

== Installation ==

1. 플러그인 ZIP 파일을 업로드하거나 GitHub Release에서 내려받은 패키지를
   `wp-content/plugins/nalapps-easy-smtp` 경로에 설치합니다.
2. WordPress 관리자에서 플러그인을 활성화합니다.
3. 관리자 메뉴의 "간편 SMTP > 메일 설정"에서 메일 서비스를 선택하거나 직접
   입력으로 접속 정보를 채우고 저장합니다.
4. "테스트 이메일"에서 테스트 메일을 발송해 연결을 확인합니다.

== Frequently Asked Questions ==

= 비밀번호와 API 키는 어떻게 저장되나요? =

이 사이트의 고유 인증 키(wp_salt)에서 파생된 키로 AES-256-CBC 암호화되어
저장됩니다. 다른 사이트로 옮기면 해독할 수 없으므로 설정 백업 파일에는
포함되지 않습니다.

= 라이선스 등록이 필요한가요? =

아니요. 이 플러그인은 무료 다운로드형 제품으로 시리얼키 등록 없이 항상
활성 상태이며 모든 기능과 업데이트를 바로 사용할 수 있습니다.

= SMTP 계정 없이 API 키만으로 설정할 수 있나요? =

네. Brevo, Mailgun, SendGrid는 API 키 연결 방식을 지원합니다. 연결 방식에서
"API 키"를 선택하면 아이디/비밀번호 없이 API 키 하나로 발송할 수 있습니다.

== Changelog ==

= 1.1.2 =
* NalApps WordPress Plugin Standard 4.7.0의 `free_download` 라이선스 계약 적용
* 무료 다운로드형 라이선스 상태를 키 입력 없이 항상 활성 상태로 검증하는 품질/릴리스 게이트 추가
* WordPress Coding Standards 오류를 수정하고 PHP 8.1~8.5 품질 게이트 통과
* GitHub 저장소명과 플러그인 슬러그 차이로 발생하던 공식 WordPress Plugin Check 경로 문제 수정
* 검증된 GitHub Release Asset ZIP과 SHA-256 파일을 생성하는 릴리스 파이프라인 복구

= 1.1.1 =
* 테스트 메일/API 키 발송 실패 시 "발송했습니다"로 잘못 표시되던 버그 수정 (pre_wp_mail이 WP_Error를 반환하면 실패로 정확히 처리)
* API 발송 실패 시 제공자(Brevo/Mailgun/SendGrid) 응답 메시지를 화면에 함께 표시하여 원인 파악이 쉬워짐

= 1.1.0 =
* Brevo/Mailgun/SendGrid 빠른 설정(로고 선택/드롭다운) 및 가입 바로가기 추가
* SMTP 계정 또는 API 키 연결 방식 선택 기능 추가 (Brevo/Mailgun/SendGrid API 발송 지원)
* 무료 제품으로 전환: 라이선스 등록 절차 제거, 라이선스 화면은 무료 상태만 표시
* 업데이트 확인을 EDD 대신 GitHub Release 기준으로 전환(라이선스 없이 동작)
* "메일 발송 사용" 항목을 체크박스에서 스위치 버튼으로 변경

= 1.0.0 =
* NalApps Commerce Core 애드온에서 독립 플러그인으로 최초 출시
* 기존 SMTP 설정(암호화 저장 방식 포함)을 그대로 유지
* 제품 자체 라이선스, 하이브리드 업데이트, 롤백, 데이터 백업/복원, 시스템 정보 추가

== Upgrade Notice ==

= 1.1.2 =
무료 다운로드형 라이선스 계약과 릴리스/업데이트 패키지 검증을 정식 표준에 맞춘 안정화 업데이트입니다.

= 1.1.1 =
API 키 모드 발송 실패가 "성공"으로 잘못 표시되던 버그를 수정했습니다. Brevo/Mailgun/SendGrid를 API 키로 연결하신 분은 업데이트를 권장합니다.

= 1.1.0 =
무료 전환 업데이트입니다. 라이선스 등록 없이 계속 사용할 수 있으며, 기존
SMTP 설정은 그대로 유지됩니다.
