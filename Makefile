
SHELL=/bin/bash

.PHONY: help
help:
	@echo "Run with CI targets."

.PHONY: ci-spec
ci-spec:
	./vendor/bin/phpspec run
	./vendor/bin/phpcov merge --clover=spec.xml coverage/spec.cov
	bash <(curl -s https://codecov.io/bash) -C $$GITHUB_SHA -B $${GITHUB_REF#refs/heads/} -c -F spec -f spec.xml

.PHONY: ci-unit
ci-unit:
	./vendor/bin/phpunit --testsuite Unit --coverage-php=coverage/unit.cov
	./vendor/bin/phpcov merge --clover=unit.xml coverage/unit.cov
	bash <(curl -s https://codecov.io/bash) -C $$GITHUB_SHA -B $${GITHUB_REF#refs/heads/} -c -F unit -f unit.xml

.PHONY: ci-feature 
ci-feature:
	./vendor/bin/phpunit --testsuite Feature --coverage-php=coverage/feature.cov
	./vendor/bin/phpcov merge --clover=feature.xml coverage/feature.cov
	bash <(curl -s https://codecov.io/bash) -C $$GITHUB_SHA -B $${GITHUB_REF#refs/heads/} -c -F feature -f feature.xml
