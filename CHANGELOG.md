# Changelog

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
