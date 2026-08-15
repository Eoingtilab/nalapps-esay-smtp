# NalApps Easy SMTP

WordPress 메일 발송(wp_mail)을 위한 안전하고 간단한 SMTP/API 설정, 진단, 시험 발송 도구입니다. **무료 플러그인**이며 라이선스 등록이 필요하지 않습니다.

## 개발사

- 개발: **EOINGTI Lab / 어잉티연구소**
- 개발자 사이트: https://eoingti.com/
- GitHub: https://github.com/Eoingtilab/nalapps-esay-smtp
- 개발 표준: https://github.com/Eoingtilab/nalapps-wordpress-plugin-standard
- 적용 표준 버전: **NalApps WordPress Plugin Standard v4.6.0**

## 주요 기능

- Brevo / Mailgun / SendGrid 로고 선택 또는 드롭다운으로 접속 정보 자동 입력, 가입 바로가기 링크
- SMTP 계정(호스트/포트/사용자명/비밀번호) 또는 API 키 중 원하는 연결 방식 선택
- 위 3개 서비스 외의 SMTP 서버도 직접 입력으로 자유롭게 설정 가능
- 저장된 설정으로 즉시 테스트 메일 발송, 실패 시 제공자의 실제 오류 메시지 표시
- SMTP 비밀번호와 API 키는 이 사이트에서만 해독 가능하도록 암호화되어 저장(사이트 간 이동 불가)
- 무료 제품: 시리얼키 등록/활성화 절차 없이 모든 기능 사용 가능
- GitHub Release 기준 업데이트 확인 및 실행형 업데이트(EDD/라이선스 의존 없음)
- 업데이트/롤백 직전 자동 코드·설정 백업, 검증된 이전 Release로 버전 롤백
- 설정 백업(JSON) 내보내기/가져오기(비밀번호·API 키 제외)
- 비밀정보를 노출하지 않는 읽기 전용 시스템 정보 화면
- 플러그인 제거 시 기본값은 설정 보존, 명시적으로 켠 경우에만 전체 삭제

## 요구 환경

- WordPress 6.5 이상
- PHP 8.1 이상

## 설치

GitHub의 기본 **Code > Download ZIP**은 WordPress 배포 파일로 사용하지 마세요. 폴더명이 `nalapps-easy-smtp-main`으로 바뀌어 있어 업데이트 인식에 문제가 생깁니다. 반드시 [Releases](https://github.com/Eoingtilab/nalapps-esay-smtp/releases) 페이지의 공식 자산(`nalapps-easy-smtp-{버전}.zip`)을 사용하세요.

배포 ZIP의 최상위 폴더는 반드시 다음과 같아야 합니다.

```text
nalapps-easy-smtp/
```

1. [Releases](https://github.com/Eoingtilab/nalapps-esay-smtp/releases) 페이지에서 최신 `nalapps-easy-smtp-{버전}.zip`을 내려받습니다.
2. WordPress 관리자 > 플러그인 > 새로 추가 > 플러그인 업로드에서 ZIP을 올리고 활성화합니다.
3. 관리자 메뉴 **간편 SMTP > 메일 설정**에서 메일 서비스를 선택하거나 직접 입력으로 접속 정보를 채우고 저장합니다.
4. **테스트 이메일**에서 테스트 메일을 발송해 연결을 확인합니다.

자세한 설치 안내는 [`docs/install-from-github.md`](docs/install-from-github.md)를 참고하세요.

## 업데이트

이 플러그인은 라이선스 키 없이 GitHub Release를 기준으로 업데이트를 확인합니다. 관리자 메뉴 **간편 SMTP > 업데이트**에서 최신 버전을 확인하고 바로 설치할 수 있으며, 실행 직전 현재 코드와 설정은 자동으로 백업됩니다.

## 문서

- [`CHANGELOG.md`](CHANGELOG.md) — 버전별 변경 이력
- [`ROADMAP.md`](ROADMAP.md) — 향후 계획
- [`SECURITY.md`](SECURITY.md) — 보안 정책
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — 기여 가이드
- [`docs/RELEASE-ACCEPTANCE.md`](docs/RELEASE-ACCEPTANCE.md) — 릴리스 승인 체크리스트
- [`docs/install-from-github.md`](docs/install-from-github.md) — GitHub에서 설치하는 방법

## 라이선스

GPLv2 or later — [`LICENSE`](LICENSE) 참고.
