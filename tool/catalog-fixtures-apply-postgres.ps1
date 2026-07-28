param()

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$databaseUrl = $env:DATABASE_URL
if ([string]::IsNullOrWhiteSpace($databaseUrl)) {
    foreach ($file in @('.env.local', '.env')) {
        if (-not (Test-Path $file)) { continue }
        $line = Get-Content $file | Where-Object { $_ -match '^DATABASE_URL=' } | Select-Object -First 1
        if ($line) { $databaseUrl = $line.Substring('DATABASE_URL='.Length).Trim('"'); break }
    }
}
if ([string]::IsNullOrWhiteSpace($databaseUrl)) { throw 'DATABASE_URL is not configured.' }

$sql = @'
BEGIN;
DELETE FROM category_projection WHERE id IN (SELECT c.id::text FROM category c JOIN catalog x ON x.id=c.catalog_id WHERE x.object_code IN ('services','products','projects'));
DELETE FROM category WHERE catalog_id IN (SELECT id FROM catalog WHERE object_code IN ('services','products','projects'));
DELETE FROM catalog WHERE object_code IN ('services','products','projects');
INSERT INTO catalog (object_code,object_active,object_enabled,object_status,object_created_at,name,purpose,tenant) VALUES
('services',TRUE,TRUE,'active',NOW(),'Services','service-discovery','default'),
('products',TRUE,TRUE,'active',NOW(),'Products','product-commerce','default'),
('projects',TRUE,TRUE,'active',NOW(),'Projects','project-delivery','default');

CREATE TEMP TABLE fixture_category (catalog_code TEXT,name TEXT,slug TEXT,parent_slug TEXT,path TEXT,depth INT) ON COMMIT DROP;
INSERT INTO fixture_category VALUES
('services','Services','services',NULL,'services',0),
('services','Home Services','home-services','services','services.home_services',1),
('services','Business Services','business-services','services','services.business_services',1),
('services','TV Mounting','tv-mounting','home-services','services.home_services.tv_mounting',2),
('services','Furniture Assembly','furniture-assembly','home-services','services.home_services.furniture_assembly',2),
('services','Electrical and Lighting','electrical-and-lighting','home-services','services.home_services.electrical_and_lighting',2),
('services','Smart Home and Security','smart-home-and-security','home-services','services.home_services.smart_home_and_security',2),
('services','Appliance Repair','appliance-repair','home-services','services.home_services.appliance_repair',2),
('services','IT Support','it-support','business-services','services.business_services.it_support',2),
('services','Network Installation','network-installation','business-services','services.business_services.network_installation',2),
('services','Digital Marketing','digital-marketing','business-services','services.business_services.digital_marketing',2),
('products','Products','products',NULL,'products',0),
('products','Electronics','electronics','products','products.electronics',1),
('products','Home and Office','home-and-office','products','products.home_and_office',1),
('products','TVs and Displays','tvs-and-displays','electronics','products.electronics.tvs_and_displays',2),
('products','Cameras','cameras','electronics','products.electronics.cameras',2),
('products','Networking','networking','electronics','products.electronics.networking',2),
('products','Mounts and Hardware','mounts-and-hardware','home-and-office','products.home_and_office.mounts_and_hardware',2),
('products','Lighting Fixtures','lighting-fixtures','home-and-office','products.home_and_office.lighting_fixtures',2),
('products','Furniture','furniture','home-and-office','products.home_and_office.furniture',2),
('projects','Projects','projects',NULL,'projects',0),
('projects','Residential Projects','residential-projects','projects','projects.residential_projects',1),
('projects','Commercial Projects','commercial-projects','projects','projects.commercial_projects',1),
('projects','Nonprofit Projects','nonprofit-projects','projects','projects.nonprofit_projects',1),
('projects','Home Improvement','home-improvement','residential-projects','projects.residential_projects.home_improvement',2),
('projects','Smart Home Setup','smart-home-setup','residential-projects','projects.residential_projects.smart_home_setup',2),
('projects','Moving and Installation','moving-and-installation','residential-projects','projects.residential_projects.moving_and_installation',2),
('projects','Hotel Installation','hotel-installation','commercial-projects','projects.commercial_projects.hotel_installation',2),
('projects','Office Buildout','office-buildout','commercial-projects','projects.commercial_projects.office_buildout',2),
('projects','Retail Deployment','retail-deployment','commercial-projects','projects.commercial_projects.retail_deployment',2),
('projects','Community Programs','community-programs','nonprofit-projects','projects.nonprofit_projects.community_programs',2),
('projects','Technology Access','technology-access','nonprofit-projects','projects.nonprofit_projects.technology_access',2);
INSERT INTO category (catalog_id,name_entity,slug,parent_id,path,depth,locale,tenant,workflow_state,published,published_at)
SELECT x.id,f.name,f.slug,p.id::text,f.path::ltree,f.depth,'en','default','published',TRUE,NOW() FROM fixture_category f JOIN catalog x ON x.object_code=f.catalog_code LEFT JOIN category p ON p.catalog_id=x.id AND p.slug=f.parent_slug ORDER BY f.depth,f.catalog_code,f.path;
UPDATE category c SET parent_id=p.id::text FROM category p WHERE c.catalog_id=p.catalog_id AND c.depth>0 AND p.path=subpath(c.path,0,nlevel(c.path)-1);
INSERT INTO category_projection (id,slug,name_entity,parent_id,path,locale,tenant,workflow_state,published,published_at,updated_at)
SELECT c.id::text,c.slug,c.name_entity,c.parent_id,c.path::text,c.locale,c.tenant,c.workflow_state,c.published,c.published_at,NOW() FROM category c JOIN catalog x ON x.id=c.catalog_id WHERE x.object_code IN ('services','products','projects');
COMMIT;
'@

$psqlUrl = $databaseUrl -replace '\?.*$', ''
& psql.exe -X --no-psqlrc --set=ON_ERROR_STOP=1 --dbname=$psqlUrl --command=$sql
exit $LASTEXITCODE
