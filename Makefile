app_name=ldapcontacts

project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
version=2.0.3


all: dev-setup lint build-js-production test

release: npm-init build-js-production appstore

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

clean-dev:
	rm -rf node_modules

create-tag:
	git tag -a v$(version) -m "Tagging the $(version) release."
	git push origin v$(version)

check-code:
	php ../../occ app:check-code $(app_name)

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
	--exclude=js/**.js.map \
	--exclude=README.md \
	--exclude=src \
	$(project_dir)/  $(sign_dir)/$(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app files…"; \
		php ../../occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key\
			--certificate=$(cert_dir)/$(app_name).crt\
			--path=$(sign_dir)/$(app_name); \
	fi
	tar -czf $(build_dir)/$(app_name)-$(version).tar.gz \
		-C $(sign_dir) $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name)-$(version).tar.gz | openssl base64; \
	fi
