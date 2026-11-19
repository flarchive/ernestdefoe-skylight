import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import SkylightPage from './components/SkylightPage';

export default [
  new Extend.Admin().page(SkylightPage).permission(
    () => ({
      icon: 'fas fa-sliders-h',
      label: app.translator.trans('ernestdefoe-skylight.admin.permissions.use_studio'),
      permission: 'ernestdefoe-skylight.useStudio',
      allowGuest: true,
    }),
    'view'
  ),
];
