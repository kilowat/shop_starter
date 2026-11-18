# Скиллы Bitrix best practices

Каталог `skills/` — сторонний набор правил для AI-агентов, а не наш код.

- **Источник:** https://github.com/bitrix-tools/best-practice
- **Версия:** v1.4.0
- **Апстрим-коммит:** `14850b51be4d95551fe4d3c8f34e4a3c59bbf3d8` (2026-10-15)
- **Лицензия:** файл лицензии в репозитории отсутствует

## Что внутри

- `skills/bitrix-best-practice-core` — контроллеры, роутинг, request/response, JSON, кэш, события, ошибки, `Result`, логирование, валидация и т.д.
- `skills/bitrix-best-practice-sql` — ORM, `DataManager`-мапы, выборки и фильтры, запись, транзакции.

Каждый скилл — маршрутизатор: `SKILL.md` описывает, какой rule-файл из `rules/` открыть под задачу.

## Правила использования

Апстримные файлы **не правим** — иначе наше дерево разойдётся с источником и следующее обновление превратится в ручной merge.

Нужно скорректировать поведение агента под проект — пишем в корневой `AGENTS.md`: он имеет приоритет над скиллами (скиллы прямо говорят «сначала следуй более строгим правилам репозитория»).

## Обновление

```sh
git clone --depth 1 https://github.com/bitrix-tools/best-practice.git /tmp/bp
rm -rf .opencode/skills/bitrix-best-practice-*
cp -r /tmp/bp/skills/bitrix-best-practice-* .opencode/skills/
```

После копирования обновите в этом файле версию (`/tmp/bp/plugin.json`), хеш (`git -C /tmp/bp rev-parse HEAD`) и дату, затем просмотрите `git diff` — изменения правил должны быть осознанными.

## Проверка, что скиллы подхватились

```sh
opencode api get /api/skill -H "x-opencode-directory: $PWD"
```

В ответе должны появиться `bitrix-best-practice-core` и `bitrix-best-practice-sql`.