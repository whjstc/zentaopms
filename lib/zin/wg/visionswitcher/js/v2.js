if(zui.VisionSwitchMenu) return;

const {Component, ReactComponent, ComponentFromReact, jsx, signal, computed} = zui;
const {Menu, DropmenuPop} = zui.reactComponents;
const emptyFn = () => {};

class WorkspaceMenuJSX extends DropmenuPop
{
    render(props)
    {
        return this._renderPop(props);
    }

    _getData()
    {
        const data = super._getData();
        data.expandData = null;
        return data;
    }

    _handleItemClick = (info) =>
    {
        const $target = $(info.event.target);
        if ($target.closest('.tree-toggle').length || !$target.closest('.is-link').length) return;

        info.event.preventDefault();
        window.enterWorkspace(info.renderedItem['data-type'], info.renderedItem['data-url']);
        zui.Dropdown.query('#versionSwitchBtn').hide();
    };
}

class VisionSwitchMenuJSX extends ReactComponent
{
    spaceType$ = signal('');

    $spcae = computed(() =>
    {
        const spaces = this.props.spaces;
        if(!spaces || !spaces.length) return null;
        const spaceType = this.spaceType$.value;
        if(!spaceType) return null;
        return spaces.find(space => space.key === spaceType);
    });

    constructor(props)
    {
        super(props);
        this.spaceType$.value = props.currentSpace || '';
    }

    _handleClickVision(vision)
    {
        window.selectVision(vision);
    }

    _handleClickWorkspace(spaceType)
    {
        this.spaceType$.value = spaceType;
    }

    _renderMenu(props)
    {
        const {visions, currentVision, spaces, switchVisionText, switchWorkspaceText} = props;
        const items = [{type: 'heading', text: switchVisionText}];
        visions.forEach(vision => items.push({
            key     : vision.key,
            text    : vision.text,
            selected: vision.key === currentVision,
            icon    : `${vision.icon} vision-icon`,
            onClick : () => this._handleClickVision(vision.key),
        }));

        if(spaces.length)
        {
            const currentSpace = this.spaceType$.value;
            items.push({type: 'heading', text: switchWorkspaceText});
            spaces.forEach(space => items.push({
                key     : space.key,
                text    : space.text,
                selected: space.key === currentSpace,
                icon    : `${space.icon} workspace-icon`,
                trailingIcon: 'icon-angle-right',
                onClick : () => this._handleClickWorkspace(space.key),
            }));
        }

        return jsx`<div class="vision-vision-menu-main flex-none"><${Menu} items=${items} /></div>`;
    }

    _renderWorkspace(props)
    {
        const space = this.$spcae.value;
        if(!space) return null;

        const url   = space.fetcher || $.createLink(space.key, 'ajaxGetDropMenu', `${space.key}ID=0`);
        const state = {value: '', data: {search: false}};
        return jsx`<div class="vision-workspace-menu border-l"><${WorkspaceMenuJSX} key=${space.key} fetcher=${url} state=${state} togglePop=${emptyFn} searchBox=${false} /></div>`;
    }

    render(props)
    {
        return jsx`<div class="vision-switch-menu not-hide-menu row items-stretch">
          ${this._renderMenu(props)}
          ${this._renderWorkspace(props)}
        </div>`;
    }
}

class VisionSwitchMenu extends ComponentFromReact
{
    static NAME = 'VisionSwitchMenu';

    static Component = VisionSwitchMenuJSX;
}

zui.VisionSwitchMenu = VisionSwitchMenu;
VisionSwitchMenu.register();
