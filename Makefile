app_name=ldapcontacts

project_dir=$(CURDIR)/../$(app_name)
nextcloud_container=nc21-h-software-de_nextcloud
docker_project_dir=/var/www/html/custom_apps/$(app_name)
build_dir=$(CURDIR)/build/artifacts
docker_build_dir=$(docker_project_dir)/build/artifacts
sign_dir=$(build_dir)/sign
docker_sign_dir=$(docker_build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
version=2.0.5


all: dev-setup lint build-js-production test

release: appstore

# Dev env management
dev-setup: clean clean-dev npm-init

npm-init:
	npm install

npm-update:
	npm update

npm-upgrade:
	npm install -g npm-check-updates
	ncu -u

# Building
build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

# Testing
test:
	npm run test

test-watch:
	npm run test:watch

test-coverage:
	npm run test:coverage

# Linting
lint:
	npm run lint

lint-fix:
	npm run lint:fix

# Style linting
stylelint:
	npm run stylelint

stylelint-fix:
	npm run stylelint:fix

# Cleaning
clean:
	rm -f js/$(app_name)_*.js
	rm -f js/$(app_name)_*.js.map
	rm -f css/*.css

clean-dev:
	rm -rf node_modules

create-tag:
	git tag -a v$(version) -m "Tagging the $(version) release."
	git push origin v$(version)

check-code:
	docker exec -it -u 33 $(nextcloud_container) php ./occ app:check-code $(app_name)

appstore: npm-init build-js-production check-code
	rm -rf $(build_dir)
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=.git \
	--exclude=build \
	--exclude=node_modules \
	--exclude=.babelrc.js \
	--exclude=.eslintrc.js \
	--exclude=.gitignore \
	--exclude=.stylelintrc.js \
	--exclude=Makefile \
	--exclude=package.json \
	--exclude=package-lock.json \
	--exclude=webpack.common.js \
	--exclude=webpack.dev.js \
	--exclude=webpack.prod.js \
	--exclude=js/**.js.* \
	--exclude=README.md \
	--exclude=src \
	--exclude=css/**.scss \
	$(project_dir)/  $(sign_dir)/$(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app files…"; \
		mkdir -p certs; \
		cp -r $(cert_dir)/$(app_name).* certs; \
		docker exec -it -u 33 $(nextcloud_container) php ./occ integrity:sign-app \
			--privateKey=$(docker_project_dir)/certs/$(app_name).key\
			--certificate=$(docker_project_dir)/certs/$(app_name).crt\
			--path=$(docker_sign_dir)/$(app_name); \
		rm -r certs; \
	fi
	tar -czf $(build_dir)/$(app_name)-$(version).tar.gz \
		-C $(sign_dir) $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name)-$(version).tar.gz | openssl base64; \
	fi
