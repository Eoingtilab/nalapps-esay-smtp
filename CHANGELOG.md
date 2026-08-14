# Changelog

## 1.1.0
- Brevo / Mailgun / SendGrid 빠른 설정(로고 선택 또는 드롭다운) 및 가입 바로가기 링크 추가
- SMTP 계정 또는 API 키 연결 방식을 선택할 수 있는 기능 추가 (Brevo/Mailgun/SendGrid HTTP API 발송 지원, `pre_wp_mail` 필터 기반)
- 무료 제품으로 전환: EDD 라이선스/EDD SL SDK 제거, 라이선스 화면은 "무료(활성)" 상태만 표시
- 업데이트 확인/설치를 EDD 대신 GitHub Releases API 기준으로 전환 (라이선스 없이 동작, "확인할 수 없음" 문제 해결)
- 설정 백업/내보내기에서 API 키 필드(`api_key_enc`)도 비밀번호와 동일하게 항상 제외
- "메일 발송 사용" 체크박스를 스위치 버튼으로 변경
- 관리 메뉴/화면 제목을 "SMTP 설정" → "메일 설정"으로 통일

## 1.0.0
- NalApps Commerce Core 애드온(`nalapps-commerce-addon-easy-smtp`)에서 독립 플러그인으로 최초 출시
- 기존 설정 옵션(`nalapps_easy_smtp_settings`)과 AES-256-CBC 암호화 비밀번호 저장 방식을 변경 없이 유지
- 제품 자체 EDD 라이선스 관리 화면(등록/확인/비활성화) 추가
- EDD + GitHub Release 하이브리드 업데이트, 실행형 Update now 추가
- 업데이트/롤백 직전 자동 코드·설정 백업 및 검증된 이전 Release로의 버전 롤백 추가
- 설정 백업(JSON) 내보내기/가져오기 추가 (비밀번호 필드는 항상 제외)
- 비밀정보를 제외한 시스템 정보 및 Site Health 진단 정보 추가
- 플러그인 제거 시 기본 데이터 보존, 명시적 옵트인 시에만 전체 삭제
- NalApps 공통 관리자 UI(Kit v4.6.0)를 이 제품 전용 내비게이션으로 적용
