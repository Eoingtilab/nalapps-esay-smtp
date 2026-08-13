=== NalApps Easy SMTP ===
Contributors: eoingtilab
Tags: smtp, email, mail, phpmailer, deliverability
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress 메일 발송을 위한 안전하고 간단한 SMTP 설정, 진단, 시험 발송 도구입니다.

== Description ==

NalApps Easy SMTP는 WordPress의 기본 메일 발송(wp_mail)이 SMTP 서버를 통해
안정적으로 전달되도록 연결 정보를 등록하고, 저장된 정보로 실제 테스트 메일을
발송해 발송 상태를 즉시 확인할 수 있는 플러그인입니다.

= 주요 기능 =

* SMTP 호스트, 포트, 암호화(TLS/SSL/없음), 사용자명, 비밀번호, 발신자 정보 설정
* 저장된 설정으로 즉시 테스트 메일 발송
* SMTP 비밀번호는 이 사이트에서만 해독 가능하도록 암호화되어 저장(사이트 간 이동 불가)
* EDD 기반 시리얼키 라이선스 등록/확인/비활성화
* 라이선스 서버와 GitHub Release를 함께 사용하는 하이브리드 업데이트
* 업데이트/롤백 직전 자동 코드·설정 백업, 검증된 이전 Release로 버전 롤백
* 설정 백업(JSON) 내보내기/가져오기(비밀번호 제외)
* 라이선스 키, 비밀번호 등 비밀정보를 노출하지 않는 읽기 전용 시스템 정보 화면
* 플러그인 제거 시 기본값은 설정 보존, 명시적으로 켠 경우에만 전체 삭제

== Installation ==

1. 플러그인 ZIP 파일을 업로드하거나 GitHub Release에서 내려받은 패키지를
   `wp-content/plugins/nalapps-easy-smtp` 경로에 설치합니다.
2. WordPress 관리자에서 플러그인을 활성화합니다.
3. 관리자 메뉴의 "간편 SMTP > SMTP 설정"에서 SMTP 서버 정보를 입력하고 저장합니다.
4. "테스트 이메일"에서 테스트 메일을 발송해 연결을 확인합니다.

== Frequently Asked Questions ==

= 비밀번호는 어떻게 저장되나요? =

이 사이트의 고유 인증 키(wp_salt)에서 파생된 키로 AES-256-CBC 암호화되어
저장됩니다. 다른 사이트로 옮기면 해독할 수 없으므로 설정 백업 파일에는
비밀번호가 포함되지 않습니다.

= 라이선스가 없어도 메일 발송이 되나요? =

네. 라이선스는 업데이트 설치 권한에만 사용되며, SMTP 발송 기능 자체는
라이선스 상태와 무관하게 계속 동작합니다.

== Changelog ==

= 1.0.0 =
* NalApps Commerce Core 애드온에서 독립 플러그인으로 최초 출시
* 기존 SMTP 설정(암호화 저장 방식 포함)을 그대로 유지
* 제품 자체 라이선스, 하이브리드 업데이트, 롤백, 데이터 백업/복원, 시스템 정보 추가

== Upgrade Notice ==

= 1.0.0 =
독립 플러그인으로 최초 릴리스입니다. 기존 NalApps Commerce Core 애드온
버전을 사용 중이었다면 이 플러그인이 동일한 설정을 그대로 이어받습니다.
