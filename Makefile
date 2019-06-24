
SHELL=/bin/bash

.PHONY: help
help:
	@echo "Use make test to run tests, make coverage-html to generate report in coverage/html."

.PHONY: test
test: test-spec test-unit test-feature

.PHONY: test-spec
test-spec:
	./vendor/bin/phpspec run
	./vendor/bin/phpcov merge --clover=spec.xml coverage/spec.cov

.PHONY: ci-spec
ci-spec: test-spec
	bash <(curl -s https://codecov.io/bash) -C $$GITHUB_SHA -B $${GITHUB_REF#refs/heads/} -c -F spec -f spec.xml

.PHONY: test-unit
test-unit:
	./vendor/bin/phpunit --testsuite Unit --coverage-php=coverage/unit.cov
	./vendor/bin/phpcov merge --clover=unit.xml coverage/unit.cov

.PHONY: ci-unit
ci-unit: test-unit
	bash <(curl -s https://codecov.io/bash) -C $$GITHUB_SHA -B $${GITHUB_REF#refs/heads/} -c -F unit -f unit.xml

.PHONY: test-feature
test-feature:
	./vendor/bin/phpunit --testsuite Feature --coverage-php=coverage/feature.cov
	./vendor/bin/phpcov merge --clover=feature.xml coverage/feature.cov

.PHONY: ci-feature
ci-feature: test-feature
	bash <(curl -s https://codecov.io/bash) -C $$GITHUB_SHA -B $${GITHUB_REF#refs/heads/} -c -F feature -f feature.xml

.PHONY: coverage-html
coverage-html:
	./vendor/bin/phpcov merge --html=coverage/html coverage/

.PHONY: clean-coverage
clean-coverage:
	rm -rf coverage/*
