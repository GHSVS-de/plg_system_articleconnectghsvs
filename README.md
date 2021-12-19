# plg_system_articleconnectghsvs
Joomla system plugin. Connect articles ("Cross linking").

## Work in progress
**Not yet usable. Kills your website.**

## Be aware
- This plugin has been only restored on GitHub because it's still in use on two websites and has to be ported to Joomla 4.
- It is complicated.
- It contains convoluted code.
- It is not preformant.
- It doesn't contain output routines to display links in posts on the frontend.
- It contains hard-coded MySQL code.
- It was a nice idea then but ended up as f'ing code hell.
- There are better extensions on the market like Article Field of Regular Labs.

-----------------------------------------------------

# My personal build procedure (WSL 1, Debian, Win 10)
- Prepare/adapt `./package.json`.
- `cd /mnt/z/git-kram/plg_system_articleconnectghsvs`

## node/npm updates/installation
- `npm run g-npm-update-check` or (faster) `ncu`
- `npm run g-ncu-override-json` (if needed) or (faster) `ncu -u`
- `npm install` (if needed)

## Build installable ZIP package
- `node build.js`
- New, installable ZIP is in `./dist` afterwards.
- All packed files for this ZIP can be seen in `./package`. **But only if you disable deletion of this folder at the end of `build.js`**.s

### For Joomla update and changelog server
- Create new release with new tag.
- - See release description in `dist/release.txt`.
- Extracts(!) of the update and changelog XML for update and changelog servers are in `./dist` as well. Copy/paste and necessary additions.
