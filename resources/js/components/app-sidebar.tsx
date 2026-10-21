import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Boxes,
    Building2,
    Clock,
    FolderGit2,
    Globe,
    LayoutGrid,
    ListFilter,
    ListTree,
    Mail,
    MailOpen,
    Map,
    MapPinned,
    Menu,
    Shield,
    Star,
    Store,
    UserCog,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as mailbox } from '@/routes/mailbox';
import { index as branches } from '@/routes/master/branches';
import { index as cities } from '@/routes/master/cities';
import { index as countries } from '@/routes/master/countries';
import { index as dropdowns } from '@/routes/master/dropdowns';
import { index as optionValues } from '@/routes/master/dropdown-option-values';
import { index as emailAccounts } from '@/routes/master/email-accounts';
import { index as editorSettings } from '@/routes/master/editor-settings';
import { index as emailTypes } from '@/routes/master/email-types';
import { index as groups } from '@/routes/master/groups';
import { index as menus } from '@/routes/master/menus';
import { index as modules } from '@/routes/master/modules';
import { index as regions } from '@/routes/master/regions';
import { index as roles } from '@/routes/master/roles';
import { index as states } from '@/routes/master/states';
import { index as timezones } from '@/routes/master/timezones';
import { index as users } from '@/routes/master/users';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Mail',
        href: mailbox(),
        icon: Mail,
    },
];

const masterNavItems: NavItem[] = [
    {
        title: 'Modules',
        href: modules(),
        icon: Boxes,
    },
    {
        title: 'Dropdowns',
        href: dropdowns(),
        icon: ListFilter,
    },
    {
        title: 'Option Values',
        href: optionValues(),
        icon: ListTree,
    },
    {
        title: 'Menus',
        href: menus(),
        icon: Menu,
    },
    {
        title: 'Roles',
        href: roles(),
        icon: Shield,
    },
    {
        title: 'Users',
        href: users(),
        icon: UserCog,
    },
    {
        title: 'Email types',
        href: emailTypes(),
        icon: Mail,
    },
    {
        title: 'Editor settings',
        href: editorSettings(),
        icon: Star,
    },
    {
        title: 'Email accounts',
        href: emailAccounts(),
        icon: MailOpen,
    },
    {
        title: 'Timezones',
        href: timezones(),
        icon: Clock,
    },
    {
        title: 'Countries',
        href: countries(),
        icon: Globe,
    },
    {
        title: 'States',
        href: states(),
        icon: MapPinned,
    },
    {
        title: 'Cities',
        href: cities(),
        icon: Building2,
    },
    {
        title: 'Groups',
        href: groups(),
        icon: Users,
    },
    {
        title: 'Regions',
        href: regions(),
        icon: Map,
    },
    {
        title: 'Branches',
        href: branches(),
        icon: Store,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMain items={masterNavItems} label="Master" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
