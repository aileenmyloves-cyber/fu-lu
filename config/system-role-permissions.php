<?php


return [
    'default-permission'                                =>  [
        'title'                                         => 'Default Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'Currency',
                'routes'                                => [
                    [
                        'title'                         => 'Currency List',
                        'route'                         => 'admin.currency.index'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.currency.store'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.currency.edit'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.currency.update'
                    ],
                    [
                        'title'                         => 'Delete',
                        'route'                         => 'admin.currency.delete'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.currency.status.update'
                    ],
                    [
                        'title'                         => 'Search',
                        'route'                         => 'admin.currency.search'
                    ],
                    [
                        'title'                         => 'Bulk Enable',
                        'route'                         => 'admin.currency.bulk.status.enable'
                    ],
                    [
                        'title'                         => 'Bulk Disable',
                        'route'                         => 'admin.currency.bulk.status.disable'
                    ],
                ],
            ],
            [
                'title'                                 => 'Precision',
                'routes'                                => [
                    [
                        'title'                         => 'Setup Precision',
                        'route'                         => 'admin.currency.precision.setup'
                    ]
                ],
            ],
            [
                'title'                                 => 'Live Exchange Rate',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.live.exchange.rate.index'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.live.exchange.rate.edit'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.live.exchange.rate.update'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.live.exchange.rate.status.update'
                    ],
                    [
                        'title'                         => 'Search',
                        'route'                         => 'admin.live.exchange.rate.search'
                    ],
                    [
                        'title'                         => 'Module Permission',
                        'route'                         => 'admin.live.exchange.rate.module.permission'
                    ],
                    [
                        'title'                         => 'Send Request',
                        'route'                         => 'admin.live.exchange.rate.send.request'
                    ],

                ],
            ],
            [
                'title'                                 => 'Fees & Charge',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.trx.settings.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.trx.settings.charges.update'
                    ],

                ],
            ],
            [
                'title'                                 => 'Virtual Card',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.virtual.card.api'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.virtual.card.api.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Gift Card',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.gift.card.index'
                    ],
                    [
                        'title'                         => 'Update Credentials',
                        'route'                         => 'admin.gift.card.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Payment Link Api',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.gateway.api.index'
                    ],
                    [
                        'title'                         => 'Update Wallet Status',
                        'route'                         => 'admin.gateway.api.update.wallet.status'
                    ],
                    [
                        'title'                         => 'Update Payment Gateway Status',
                        'route'                         => 'admin.gateway.api.update.payment.gateway.status'
                    ],
                    [
                        'title'                         => 'Update Card Status',
                        'route'                         => 'admin.gateway.api.update.card.status'
                    ],
                    [
                        'title'                         => 'Update Card Credentials',
                        'route'                         => 'admin.gateway.api.update.card.credentials'
                    ],
                ],
            ],
            [
                'title'                                 => 'Bill Pay Method Automatic',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.bill.pay.method.automatic.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.bill.pay.method.automatic.api.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Bill Pay Method Manual',
                'routes'                                => [
                    [
                        'title'                         => 'Category Index',
                        'route'                         => 'admin.bill.pay.method.manual.category.index'
                    ],
                    [
                        'title'                         => 'Category Store',
                        'route'                         => 'admin.bill.pay.method.manual.category.store'
                    ],
                    [
                        'title'                         => 'Category Status Update',
                        'route'                         => 'admin.bill.pay.method.manual.category.status.update'
                    ],
                    [
                        'title'                         => 'Category Update',
                        'route'                         => 'admin.bill.pay.method.manual.category.update'
                    ],
                    [
                        'title'                         => 'Category Delete',
                        'route'                         => 'admin.bill.pay.method.manual.category.delete'
                    ],
                    [
                        'title'                         => 'Category Search',
                        'route'                         => 'admin.bill.pay.method.manual.category.search'
                    ],
                ],
            ],
            [
                'title'                                 => 'Mobile Topup Method Automatic',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.mobile.topup.method.automatic.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.mobile.topup.method.automatic.api.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Mobile Topup Method Manual',
                'routes'                                => [
                    [
                        'title'                         => 'Category Index',
                        'route'                         => 'admin.mobile.topup.method.manual.index'
                    ],
                    [
                        'title'                         => 'Category Store',
                        'route'                         => 'admin.mobile.topup.method.manual.store'
                    ],
                    [
                        'title'                         => 'Category Status Update',
                        'route'                         => 'admin.mobile.topup.method.manual.status.update'
                    ],
                    [
                        'title'                         => 'Category Update',
                        'route'                         => 'admin.mobile.topup.method.manual.update'
                    ],
                    [
                        'title'                         => 'Category Delete',
                        'route'                         => 'admin.mobile.topup.method.manual.delete'
                    ],
                    [
                        'title'                         => 'Category Search',
                        'route'                         => 'admin.mobile.topup.method.manual.search'
                    ],
                ],
            ],

        ],
    ],
    'transaction-log-permission'                                           => [
        'title'                                         => 'Transaction Logs Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'Add Money Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.add.money.index'
                    ],
                    [
                        'title'                         => 'Pending',
                        'route'                         => 'admin.add.money.pending'
                    ],
                    [
                        'title'                         => 'Complete',
                        'route'                         => 'admin.add.money.complete'
                    ],
                    [
                        'title'                         => 'Canceled',
                        'route'                         => 'admin.add.money.canceled'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.add.money.details'
                    ],
                    [
                        'title'                         => 'Approved',
                        'route'                         => 'admin.add.money.approved'
                    ],
                    [
                        'title'                         => 'Rejected',
                        'route'                         => 'admin.add.money.rejected'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.add.money.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Withdraw Money Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.money.out.index'
                    ],
                    [
                        'title'                         => 'Pending',
                        'route'                         => 'admin.money.out.pending'
                    ],
                    [
                        'title'                         => 'Complete',
                        'route'                         => 'admin.money.out.complete'
                    ],
                    [
                        'title'                         => 'Canceled',
                        'route'                         => 'admin.money.out.canceled'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.money.out.details'
                    ],
                    [
                        'title'                         => 'Approved',
                        'route'                         => 'admin.money.out.approved'
                    ],
                    [
                        'title'                         => 'Rejected',
                        'route'                         => 'admin.money.out.rejected'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.money.out.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Remittance Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.remitance.index'
                    ],
                    [
                        'title'                         => 'Pending',
                        'route'                         => 'admin.remitance.pending'
                    ],
                    [
                        'title'                         => 'Complete',
                        'route'                         => 'admin.remitance.complete'
                    ],
                    [
                        'title'                         => 'Canceled',
                        'route'                         => 'admin.remitance.canceled'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.remitance.details'
                    ],
                    [
                        'title'                         => 'Approved',
                        'route'                         => 'admin.remitance.approved'
                    ],
                    [
                        'title'                         => 'Rejected',
                        'route'                         => 'admin.remitance.rejected'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.remitance.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Bill Pay Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.bill.pay.index'
                    ],
                    [
                        'title'                         => 'Pending',
                        'route'                         => 'admin.bill.pay.pending'
                    ],
                    [
                        'title'                         => 'Processing',
                        'route'                         => 'admin.bill.pay.processing'
                    ],
                    [
                        'title'                         => 'Complete',
                        'route'                         => 'admin.bill.pay.complete'
                    ],
                    [
                        'title'                         => 'Canceled',
                        'route'                         => 'admin.bill.pay.canceled'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.bill.pay.details'
                    ],
                    [
                        'title'                         => 'Approved',
                        'route'                         => 'admin.bill.pay.approved'
                    ],
                    [
                        'title'                         => 'Rejected',
                        'route'                         => 'admin.bill.pay.rejected'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.bill.pay.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Mobile Topup Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.mobile.topup.index'
                    ],
                    [
                        'title'                         => 'Pending',
                        'route'                         => 'admin.mobile.topup.pending'
                    ],
                    [
                        'title'                         => 'Processing',
                        'route'                         => 'admin.mobile.topup.processing'
                    ],
                    [
                        'title'                         => 'Complete',
                        'route'                         => 'admin.mobile.topup.complete'
                    ],
                    [
                        'title'                         => 'Canceled',
                        'route'                         => 'admin.mobile.topup.canceled'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.mobile.topup.details'
                    ],
                    [
                        'title'                         => 'Approved',
                        'route'                         => 'admin.mobile.topup.approved'
                    ],
                    [
                        'title'                         => 'Rejected',
                        'route'                         => 'admin.mobile.topup.rejected'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.mobile.topup.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Send Money Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.send.money.index'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.send.money.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Exchange Money Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.exchange.money.index'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.exchange.money.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Money Out Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.agent.money.out.index'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.agent.money.out.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Make Payment Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.make.payment.index'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.make.payment.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Money In Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.money.in.index'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.money.in.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Request Money Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.request.money.index'
                    ],
                    [
                        'title'                         => 'Pending',
                        'route'                         => 'admin.request.money.pending'
                    ],
                    [
                        'title'                         => 'Complete',
                        'route'                         => 'admin.request.money.complete'
                    ],
                    [
                        'title'                         => 'Canceled',
                        'route'                         => 'admin.request.money.canceled'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.request.money.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Virtual Card Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.virtual.card.logs'
                    ],
                    [
                        'title'                         => 'Export data',
                        'route'                         => 'admin.virtual.card.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Payment Link Logs',
                'routes'                                => [
                    [
                        'title'                         => 'Transaction Logs',
                        'route'                         => 'admin.payment.link.index'
                    ],
                    [
                        'title'                         => 'All Link',
                        'route'                         => 'admin.payment.link.all.link'
                    ],
                    [
                        'title'                         => 'Active Link',
                        'route'                         => 'admin.payment.link.active.link'
                    ],
                    [
                        'title'                         => 'Closed Link',
                        'route'                         => 'admin.payment.link.closed.link'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.payment.link.details'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.payment.link.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Gift Card Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.gift.card.logs'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.gift.card.details'
                    ],
                    [
                        'title'                         => 'Search',
                        'route'                         => 'admin.gift.card.search'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.gift.card.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Trade Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.trade.index'
                    ],
                    [
                        'title'                         => 'Pending Logs',
                        'route'                         => 'admin.trade.pending'
                    ],
                    [
                        'title'                         => 'Ongoing Logs',
                        'route'                         => 'admin.trade.ongoing'
                    ],
                    [
                        'title'                         => 'Complete Logs',
                        'route'                         => 'admin.trade.complete'
                    ],
                    [
                        'title'                         => 'Canceled Logs',
                        'route'                         => 'admin.trade.canceled'
                    ],
                    [
                        'title'                         => 'Close Request Logs',
                        'route'                         => 'admin.trade.cancel.request'
                    ],
                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.trade.details'
                    ],
                    [
                        'title'                         => 'Approve Trade',
                        'route'                         => 'admin.trade.approved'
                    ],
                    [
                        'title'                         => 'Reject Trade',
                        'route'                         => 'admin.trade.rejected'
                    ],

                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.trade.export.data'
                    ],
                ],
            ],
            [
                'title'                                 => 'Marketplace Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.marketplace.index'
                    ],
                    [
                        'title'                         => 'Pending Logs',
                        'route'                         => 'admin.marketplace.pending'
                    ],
                    [
                        'title'                         => 'Complete Logs',
                        'route'                         => 'admin.marketplace.complete'
                    ],
                    [
                        'title'                         => 'Canceled Logs',
                        'route'                         => 'admin.marketplace.canceled'
                    ],

                    [
                        'title'                         => 'Details',
                        'route'                         => 'admin.marketplace.details'
                    ],
                    [
                        'title'                         => 'Approve Marketplace',
                        'route'                         => 'admin.marketplace.approved'
                    ],
                    [
                        'title'                         => 'Reject Marketplace',
                        'route'                         => 'admin.marketplace.rejected'
                    ],

                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.marketplace.export.data'
                    ],
                ],
            ],

            [
                'title'                                 => 'Admin Profit Logs',
                'routes'                                => [
                    [
                        'title'                         => 'All Logs',
                        'route'                         => 'admin.profit.logs.index'
                    ],
                    [
                        'title'                         => 'Export Data',
                        'route'                         => 'admin.profit.logs.export.data'
                    ],
                ],
            ],
        ],
    ],
    'interface-permission'                                         => [
        'title'                                         => 'Interface Panel Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'User Care',
                'routes'                                => [
                    [
                        'title'                         => 'User List',
                        'route'                         => 'admin.users.index'
                    ],
                    [
                        'title'                         => 'Active Users',
                        'route'                         => 'admin.users.active'
                    ],
                    [
                        'title'                         => 'Banned Users',
                        'route'                         => 'admin.users.banned'
                    ],
                    [
                        'title'                         => 'Email Unverified',
                        'route'                         => 'admin.users.email.unverified'
                    ],
                    [
                        'title'                         => 'SMS Unverified',
                        'route'                         => 'admin.users.sms.unverified'
                    ],
                    [
                        'title'                         => 'KYC Unverified',
                        'route'                         => 'admin.users.kyc.unverified'
                    ],
                    [
                        'title'                         => 'KYC Details',
                        'route'                         => 'admin.users.kyc.details'
                    ],
                    [
                        'title'                         => 'Email To Users',
                        'route'                         => 'admin.users.email.users'
                    ],
                    [
                        'title'                         => 'Send Mail To Users',
                        'route'                         => 'admin.users.email.users.send'
                    ],
                    [
                        'title'                         => 'User Details',
                        'route'                         => 'admin.users.details'
                    ],
                    [
                        'title'                         => 'User Details Update',
                        'route'                         => 'admin.users.details.update'
                    ],
                    [
                        'title'                         => 'Login Logs',
                        'route'                         => 'admin.users.login.logs'
                    ],
                    [
                        'title'                         => 'Mail Logs',
                        'route'                         => 'admin.users.mail.logs'
                    ],
                    [
                        'title'                         => 'Send Mail',
                        'route'                         => 'admin.users.send.mail'
                    ],
                    [
                        'title'                         => 'Login as Member',
                        'route'                         => 'admin.users.login.as.member'
                    ],
                    [
                        'title'                         => 'Kyc Approve',
                        'route'                         => 'admin.users.kyc.approve'
                    ],
                    [
                        'title'                         => 'Kyc Reject',
                        'route'                         => 'admin.users.kyc.reject'
                    ],
                    [
                        'title'                         => 'Wallet Balance Update',
                        'route'                         => 'admin.users.wallet.balance.update'
                    ],
                    [
                        'title'                         => 'User Search',
                        'route'                         => 'admin.users.search'
                    ],
                    [
                        'title'                         => 'User Create',
                        'route'                         => 'admin.users.create'
                    ],
                    [
                        'title'                         => 'User Store',
                        'route'                         => 'admin.users.store'
                    ],
                ],
            ],
            [
                'title'                                 => 'Agent Care',
                'routes'                                => [
                    [
                        'title'                         => 'Agent List',
                        'route'                         => 'admin.agents.index'
                    ],
                    [
                        'title'                         => 'Active Agents',
                        'route'                         => 'admin.agents.active'
                    ],
                    [
                        'title'                         => 'Banned Agents',
                        'route'                         => 'admin.agents.banned'
                    ],
                    [
                        'title'                         => 'Email Unverified',
                        'route'                         => 'admin.agents.email.unverified'
                    ],
                    [
                        'title'                         => 'KYC Unverified',
                        'route'                         => 'admin.agents.kyc.unverified'
                    ],
                    [
                        'title'                         => 'KYC Details',
                        'route'                         => 'admin.agents.kyc.details'
                    ],
                    [
                        'title'                         => 'Email To Agents',
                        'route'                         => 'admin.agents.email.agents'
                    ],
                    [
                        'title'                         => 'Send Mail To Agents',
                        'route'                         => 'admin.agents.email.agents.send'
                    ],
                    [
                        'title'                         => 'Agent Details',
                        'route'                         => 'admin.agents.details'
                    ],
                    [
                        'title'                         => 'Agents Details Update',
                        'route'                         => 'admin.agents.details.update'
                    ],
                    [
                        'title'                         => 'Login Logs',
                        'route'                         => 'admin.agents.login.logs'
                    ],
                    [
                        'title'                         => 'Mail Logs',
                        'route'                         => 'admin.agents.mail.logs'
                    ],
                    [
                        'title'                         => 'Send Mail',
                        'route'                         => 'admin.agents.send.mail'
                    ],
                    [
                        'title'                         => 'Login as Member',
                        'route'                         => 'admin.agents.login.as.member'
                    ],
                    [
                        'title'                         => 'Kyc Approve',
                        'route'                         => 'admin.agents.kyc.approve'
                    ],
                    [
                        'title'                         => 'Kyc Reject',
                        'route'                         => 'admin.agents.kyc.reject'
                    ],
                    [
                        'title'                         => 'Wallet Balance Update',
                        'route'                         => 'admin.agents.wallet.balance.update'
                    ],
                    [
                        'title'                         => 'Agent Search',
                        'route'                         => 'admin.agents.search'
                    ],
                    [
                        'title'                         => 'Agent Create',
                        'route'                         => 'admin.agents.create'
                    ],
                    [
                        'title'                         => 'Agent Store',
                        'route'                         => 'admin.agents.store'
                    ],
                ],
            ],
            [
                'title'                                 => 'Merchant Care',
                'routes'                                => [
                    [
                        'title'                         => 'Merchant List',
                        'route'                         => 'admin.merchants.index'
                    ],
                    [
                        'title'                         => 'Active Merchants',
                        'route'                         => 'admin.merchants.active'
                    ],
                    [
                        'title'                         => 'Banned Merchants',
                        'route'                         => 'admin.merchants.banned'
                    ],
                    [
                        'title'                         => 'Email Unverified',
                        'route'                         => 'admin.merchants.email.unverified'
                    ],
                    [
                        'title'                         => 'SMS Unverified',
                        'route'                         => 'admin.merchants.sms.unverified'
                    ],
                    [
                        'title'                         => 'KYC Unverified',
                        'route'                         => 'admin.merchants.kyc.unverified'
                    ],
                    [
                        'title'                         => 'KYC Details',
                        'route'                         => 'admin.merchants.kyc.details'
                    ],
                    [
                        'title'                         => 'Email To Merchants',
                        'route'                         => 'admin.merchants.email.merchants'
                    ],
                    [
                        'title'                         => 'Send Mail To Merchants',
                        'route'                         => 'admin.merchants.email.merchants.send'
                    ],
                    [
                        'title'                         => 'Merchant Details',
                        'route'                         => 'admin.merchants.details'
                    ],
                    [
                        'title'                         => 'Merchant Details Update',
                        'route'                         => 'admin.merchants.details.update'
                    ],
                    [
                        'title'                         => 'Login Logs',
                        'route'                         => 'admin.merchants.login.logs'
                    ],
                    [
                        'title'                         => 'Mail Logs',
                        'route'                         => 'admin.merchants.mail.logs'
                    ],
                    [
                        'title'                         => 'Send Mail',
                        'route'                         => 'admin.merchants.send.mail'
                    ],
                    [
                        'title'                         => 'Login as Member',
                        'route'                         => 'admin.merchants.login.as.member'
                    ],
                    [
                        'title'                         => 'Kyc Approve',
                        'route'                         => 'admin.merchants.kyc.approve'
                    ],
                    [
                        'title'                         => 'Kyc Reject',
                        'route'                         => 'admin.merchants.kyc.reject'
                    ],
                    [
                        'title'                         => 'Wallet Balance Update',
                        'route'                         => 'admin.merchants.wallet.balance.update'
                    ],
                    [
                        'title'                         => 'Merchant Search',
                        'route'                         => 'admin.merchants.search'
                    ],
                    [
                        'title'                         => 'Merchant Create',
                        'route'                         => 'admin.merchants.create'
                    ],
                    [
                        'title'                         => 'User Store',
                        'route'                         => 'admin.merchants.store'
                    ],
                ],
            ],
            [
                'title'                                 => 'Admin Care',
                'routes'                                => [
                    [
                        'title'                         => 'Admin List',
                        'route'                         => 'admin.admins.index'
                    ],
                    [
                        'title'                         => 'Email All Admins',
                        'route'                         => 'admin.admins.email.admins'
                    ],
                    [
                        'title'                         => 'Delete Admin',
                        'route'                         => 'admin.admins.admin.delete'
                    ],
                    [
                        'title'                         => 'Send Email',
                        'route'                         => 'admin.admins.send.email'
                    ],
                    [
                        'title'                         => 'Search',
                        'route'                         => 'admin.admins.search'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.admins.admin.store'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.admins.admin.update'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.admins.admin.status.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Role & Permissions',
                'routes'                                => [
                    [
                        'title'                         => 'Role List',
                        'route'                         => 'admin.admins.role.index'
                    ],
                    [
                        'title'                         => 'Role Store',
                        'route'                         => 'admin.admins.role.store'
                    ],
                    [
                        'title'                         => 'Role Update',
                        'route'                         => 'admin.admins.role.update'
                    ],
                    [
                        'title'                         => 'Role Delete',
                        'route'                         => 'admin.admins.role.delete'
                    ],
                    [
                        'title'                         => 'Permission List',
                        'route'                         => 'admin.admins.role.permission.index'
                    ],
                    [
                        'title'                         => 'Permission Create',
                        'route'                         => 'admin.admins.role.permission.create'
                    ],
                    [
                        'title'                         => 'Permission Store',
                        'route'                         => 'admin.admins.role.permission.store'
                    ],
                    [
                        'title'                         => 'Permission Edit',
                        'route'                         => 'admin.admins.role.permission.edit'
                    ],
                    [
                        'title'                         => 'Permission Update',
                        'route'                         => 'admin.admins.role.permission.update'
                    ],
                    [
                        'title'                         => 'Permission Delete',
                        'route'                         => 'admin.admins.role.permission.delete'
                    ],
                    [
                        'title'                         => 'Permission View',
                        'route'                         => 'admin.admins.role.permission'
                    ],
                ],
            ],
        ],
    ],
    'settings-permission'                                          => [
        'title'                                         => 'Settings Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'Web Settings',
                'routes'                                => [
                    [
                        'title'                         => 'Basic Settings',
                        'route'                         => 'admin.web.settings.basic.settings'
                    ],
                    [
                        'title'                         => 'Basic Settings Update (User)',
                        'route'                         => 'admin.web.settings.basic.settings.update'
                    ],
                    [
                        'title'                         => 'Basic Settings Update (Agent)',
                        'route'                         => 'admin.web.settings.basic.settings.update.agent'
                    ],
                    [
                        'title'                         => 'Basic Settings Update (Merchant)',
                        'route'                         => 'admin.web.settings.basic.settings.update.merchant'
                    ],
                    [
                        'title'                         => 'Basic Settings Activation Update',
                        'route'                         => 'admin.web.settings.basic.settings.activation.update'
                    ],
                    [
                        'title'                         => 'Storage Settings',
                        'route'                         => 'admin.storage.settings.index',
                    ],
                    [
                        'title'                         => 'Storage Settings Update',
                        'route'                         => 'admin.storage.settings.update',
                    ],
                    [
                        'title'                         => 'Image Assets',
                        'route'                         => 'admin.web.settings.image.assets'
                    ],
                    [
                        'title'                         => 'Image Assets Update',
                        'route'                         => 'admin.web.settings.image.assets.update'
                    ],
                    [
                        'title'                         => 'Setup Seo',
                        'route'                         => 'admin.web.settings.setup.seo'
                    ],
                    [
                        'title'                         => 'Seo Update',
                        'route'                         => 'admin.web.settings.setup.seo.update'
                    ],

                ],
            ],
            [
                'title'                                 => 'App Settings',
                'routes'                                => [
                    [
                        'title'                         => 'Splash Screen',
                        'route'                         => 'admin.app.settings.splash.screen'
                    ],
                    [
                        'title'                         => 'Splash Screen Update',
                        'route'                         => 'admin.app.settings.splash.screen.update'
                    ],
                    [
                        'title'                         => 'Urls',
                        'route'                         => 'admin.app.settings.urls'
                    ],
                    [
                        'title'                         => 'Urls',
                        'route'                         => 'admin.app.settings.urls.update'
                    ],
                    [
                        'title'                         => 'Onboard Screens List',
                        'route'                         => 'admin.app.settings.onboard.index'
                    ],
                    [
                        'title'                         => 'Onboard Screens',
                        'route'                         => 'admin.app.settings.onboard.screens'
                    ],
                    [
                        'title'                         => 'Onboard Screen Store',
                        'route'                         => 'admin.app.settings.onboard.screen.store'
                    ],
                    [
                        'title'                         => 'Onboard Screen Update',
                        'route'                         => 'admin.app.settings.onboard.screen.update'
                    ],
                    [
                        'title'                         => 'Onboard Screen Status Update',
                        'route'                         => 'admin.app.settings.onboard.screen.status.update'
                    ],
                    [
                        'title'                         => 'Onboard Screen Delete',
                        'route'                         => 'admin.app.settings.onboard.screen.delete'
                    ]
                ],
            ],
            [
                'title'                                 => 'Setup Module',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.module.setting.index'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.module.setting.status.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Country Restriction',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.country.restriction.index'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.country.restriction.edit'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.country.restriction.update'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.country.restriction.status.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Language',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.languages.index'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.languages.store'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.languages.update'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.languages.status.update'
                    ],
                    [
                        'title'                         => 'Info',
                        'route'                         => 'admin.languages.info'
                    ],
                    [
                        'title'                         => 'Import',
                        'route'                         => 'admin.languages.import'
                    ],
                    [
                        'title'                         => 'Delete',
                        'route'                         => 'admin.languages.delete'
                    ],
                    [
                        'title'                         => 'Switch',
                        'route'                         => 'admin.languages.switch'
                    ],
                    [
                        'title'                         => 'Download',
                        'route'                         => 'admin.languages.download'
                    ],
                ],
            ],
            [
                'title'                                 => 'Referral Settings',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.settings.referral.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.settings.referral.update'
                    ],
                    [
                        'title'                         => 'Package Store',
                        'route'                         => 'admin.settings.referral.package.store'
                    ],
                    [
                        'title'                         => 'Package Update',
                        'route'                         => 'admin.settings.referral.package.update'
                    ],
                    [
                        'title'                         => 'Package Delete',
                        'route'                         => 'admin.settings.referral.package.delete'
                    ],
                    [
                        'title'                         => 'Package Delete',
                        'route'                         => 'admin.settings.referral.package.status.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'System Maintenance',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.system.maintenance.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.system.maintenance.update'
                    ],
                ],
            ],
        ],
    ],
    'verification-center'                                           => [
        'title'                                         => 'Verification Center Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'Setup Email',
                'routes'                                => [
                    [
                        'title'                         => 'Email Configuration',
                        'route'                         => 'admin.setup.email.config'
                    ],
                    [
                        'title'                         => 'Email Configuration Update',
                        'route'                         => 'admin.setup.email.config.update'
                    ],
                    [
                        'title'                         => 'Test Mail Send',
                        'route'                         => 'admin.setup.email.test.mail.send'
                    ],
                ],
            ],
            [
                'title'                                 => 'Setup SMS',
                'routes'                                => [
                    [
                        'title'                         => 'SMS Configuration',
                        'route'                         => 'admin.setup.sms.config'
                    ],
                    [
                        'title'                         => 'SMS Configuration Update',
                        'route'                         => 'admin.setup.sms.config.update'
                    ],
                    [
                        'title'                         => 'Test Code Send',
                        'route'                         => 'admin.setup.sms.test.code.send'
                    ],
                ],
            ],
            [
                'title'                                 => 'Setup KYC',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.setup.kyc.index'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.setup.kyc.edit'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.setup.kyc.update'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.setup.kyc.status.update'
                    ],
                ],
            ],

        ],
    ],
    'setup-web-content-permission'                                           => [
        'title'                                         => 'Setup Web Content Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'Setup Sections',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.setup.sections.section'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.setup.sections.section.update'
                    ],
                    [
                        'title'                         => 'Item Store',
                        'route'                         => 'admin.setup.sections.section.item.store'
                    ],
                    [
                        'title'                         => 'Item Update',
                        'route'                         => 'admin.setup.sections.section.item.update'
                    ],
                    [
                        'title'                         => 'Item Delete',
                        'route'                         => 'admin.setup.sections.section.item.delete'
                    ],
                    [
                        'title'                         => 'Blog Category Store',
                        'route'                         => 'admin.setup.sections.category.store'
                    ],
                    [
                        'title'                         => 'Blog Category Update',
                        'route'                         => 'admin.setup.sections.category.update'
                    ],
                    [
                        'title'                         => 'Blog Category Delete',
                        'route'                         => 'admin.setup.sections.category.delete'
                    ],
                    [
                        'title'                         => 'Blog Category Status Update',
                        'route'                         => 'admin.setup.sections.category.status.update'
                    ],
                    [
                        'title'                         => 'Blog Category Search',
                        'route'                         => 'admin.setup.sections.category.search'
                    ],
                    [
                        'title'                         => 'Blog Store',
                        'route'                         => 'admin.setup.sections.blog.store'
                    ],
                    [
                        'title'                         => 'Blog Edit',
                        'route'                         => 'admin.setup.sections.blog.edit'
                    ],
                    [
                        'title'                         => 'Blog Update',
                        'route'                         => 'admin.setup.sections.blog.update'
                    ],
                    [
                        'title'                         => 'Blog Status Update',
                        'route'                         => 'admin.setup.sections.blog.status.update'
                    ],
                    [
                        'title'                         => 'Blog Delete',
                        'route'                         => 'admin.setup.sections.blog.delete'
                    ],
                ],
            ],
            [
                'title'                                 => 'Header Section',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.setup.header.sections.index'
                    ],
                    [
                        'title'                         => 'Create',
                        'route'                         => 'admin.setup.header.sections.create'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.setup.header.sections.store'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.setup.header.sections.edit'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.setup.header.sections.update'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.setup.header.sections.status.update'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.setup.header.sections.delete'
                    ],
                    [
                        'title'                         => 'Page Content Index',
                        'route'                         => 'admin.setup.header.sections.page.index'
                    ],
                    [
                        'title'                         => 'Page Content Update',
                        'route'                         => 'admin.setup.header.sections.page.update'
                    ],
                    [
                        'title'                         => 'Page Content Item Store',
                        'route'                         => 'admin.setup.header.sections.page.item.store'
                    ],
                    [
                        'title'                         => 'Page Content Item Update',
                        'route'                         => 'admin.setup.header.sections.page.item.update'
                    ],
                    [
                        'title'                         => 'Page Content Item Delete',
                        'route'                         => 'admin.setup.header.sections.page.item.delete'
                    ],
                ],
            ],
            [
                'title'                                 => 'Faq Content',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.setup.header.sections.faq.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.setup.header.sections.faq.update'
                    ],
                    [
                        'title'                         => 'Item Store',
                        'route'                         => 'admin.setup.header.sections.faq.item.store'
                    ],
                    [
                        'title'                         => 'Item Update',
                        'route'                         => 'admin.setup.header.sections.faq.item.update'
                    ],
                    [
                        'title'                         => 'Item Delete',
                        'route'                         => 'admin.setup.header.sections.faq.item.delete'
                    ],
                ],
            ],
            [
                'title'                                 => 'Setup Pages',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.setup.pages.section'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.setup.pages.status.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Useful Links',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.useful.links.index'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.useful.links.store'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.useful.links.status.update'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.useful.links.edit'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.useful.links.update'
                    ],
                    [
                        'title'                         => 'Delete',
                        'route'                         => 'admin.useful.links.delete'
                    ],
                ],
            ],
            [
                'title'                                 => 'Extensions',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.extensions.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.extensions.update'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.extensions.status.update'
                    ],
                ],
            ],
        ],
    ],
    'payment-method-permission'                                           => [
        'title'                                         => 'Payment Method Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'Payment Gateway',
                'routes'                                => [
                    [
                        'title'                         => 'Create',
                        'route'                         => 'admin.payment.gateway.create'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.payment.gateway.store'
                    ],
                    [
                        'title'                         => 'View',
                        'route'                         => 'admin.payment.gateway.view'
                    ],
                    [
                        'title'                         => 'Edit',
                        'route'                         => 'admin.payment.gateway.edit'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.payment.gateway.update'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.payment.gateway.status.update'
                    ],
                    [
                        'title'                         => 'Remove',
                        'route'                         => 'admin.payment.gateway.remove'
                    ],
                ],
            ],
            [
                'title'                                 => 'Bank Deposit',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.remitance.bank.deposit.index'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.remitance.bank.deposit.store'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.remitance.bank.deposit.update'
                    ],
                    [
                        'title'                         => 'Delete',
                        'route'                         => 'admin.remitance.bank.deposit.delete'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.remitance.bank.deposit.status.update'
                    ],
                    [
                        'title'                         => 'Search',
                        'route'                         => 'admin.remitance.bank.deposit.search'
                    ],
                ],
            ],
            [
                'title'                                 => 'Cash Pickup',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.remitance.cash.pickup.index'
                    ],
                    [
                        'title'                         => 'Store',
                        'route'                         => 'admin.remitance.cash.pickup.store'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.remitance.cash.pickup.update'
                    ],
                    [
                        'title'                         => 'Delete',
                        'route'                         => 'admin.remitance.cash.pickup.delete'
                    ],
                    [
                        'title'                         => 'Status Update',
                        'route'                         => 'admin.remitance.cash.pickup.status.update'
                    ],
                    [
                        'title'                         => 'Search',
                        'route'                         => 'admin.remitance.cash.pickup.search'
                    ],
                ],
            ],
        ],
    ],
    'support-permission'                                => [
        'title'                                         => 'Support Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'Push Notification',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.push.notification.index'
                    ],
                    [
                        'title'                         => 'Config',
                        'route'                         => 'admin.push.notification.config'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.push.notification.update'
                    ],
                    [
                        'title'                         => 'Send',
                        'route'                         => 'admin.push.notification.send'
                    ],
                    [
                        'title'                         => 'Broadcast Update',
                        'route'                         => 'admin.push.notification.broadcast.config.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Support Ticket',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.support.ticket.index'
                    ],
                    [
                        'title'                         => 'Active',
                        'route'                         => 'admin.support.ticket.active'
                    ],
                    [
                        'title'                         => 'Pending',
                        'route'                         => 'admin.support.ticket.pending'
                    ],
                    [
                        'title'                         => 'Solved',
                        'route'                         => 'admin.support.ticket.solved'
                    ],
                    [
                        'title'                         => 'Conversation',
                        'route'                         => 'admin.support.ticket.conversation'
                    ],
                    [
                        'title'                         => 'Reply Message',
                        'route'                         => 'admin.support.ticket.messaage.reply'
                    ],
                    [
                        'title'                         => 'Solve',
                        'route'                         => 'admin.support.ticket.solve'
                    ],
                    [
                        'title'                         => 'Add Support Ticket',
                        'route'                         => 'admin.support.ticket.create'
                    ],
                    [
                        'title'                         => 'Store Support Ticket',
                        'route'                         => 'admin.support.ticket.store'
                    ],
                    [
                        'title'                         => 'Delete Ticket',
                        'route'                         => 'admin.support.ticket.delete'
                    ],
                    [
                        'title'                         => 'Bulk Delete Ticket',
                        'route'                         => 'admin.support.ticket.bulk.delete'
                    ],
                ],
            ],
            [
                'title'                                 => 'Contact Message',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.contact.messages.index'
                    ],
                    [
                        'title'                         => 'Delete',
                        'route'                         => 'admin.contact.messages.delete'
                    ],
                    [
                        'title'                         => 'Email Send',
                        'route'                         => 'admin.contact.messages.email.send'
                    ],

                ],
            ],
            [
                'title'                                 => 'Newsletter',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.newsletter.index'
                    ],
                    [
                        'title'                         => 'Delete',
                        'route'                         => 'admin.newsletter.delete'
                    ],
                    [
                        'title'                         => 'Search',
                        'route'                         => 'admin.newsletter.search'
                    ],
                    [
                        'title'                         => 'Send Mail',
                        'route'                         => 'admin.newsletter.send.mail'
                    ],

                ],
            ],
            [
                'title'                                 => 'Admin Notification',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.notification.index'
                    ]
                ],
            ],

        ],
    ],
    'bonus-permission'                                           => [
        'title'                                         => 'Bonus Permissions',
        'sections'                                      =>  [
            [
                'title'                                 => 'GDPR Cookie',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.cookie.index'
                    ],
                    [
                        'title'                         => 'Update',
                        'route'                         => 'admin.cookie.update'
                    ],
                ],
            ],
            [
                'title'                                 => 'Server Info',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.server.info.index'
                    ],
                ],
            ],
            [
                'title'                                 => 'Error Logs',
                'routes'                                => [
                    [
                        'title'                         => 'Index',
                        'route'                         => 'admin.error.logs.index'
                    ]
                ],
            ],
            [
                'title'                                 => 'Cache',
                'routes'                                => [
                    [
                        'title'                         => 'Cache',
                        'route'                         => 'admin.cache.clear'
                    ],
                ],
            ],
            [
                'title' => 'Sitemap',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.sitemap.index',
                    ],
                    [
                        'title' => 'Generate',
                        'route' => 'admin.sitemap.generate',
                    ],
                ],
            ],
            [
                'title' => 'File Permission',
                'routes' => [
                    [
                        'title' => 'Index',
                        'route' => 'admin.file.permission.index',
                    ],
                    [
                        'title' => 'Restore',
                        'route' => 'admin.file.permission.restore',
                    ],
                    [
                        'title' => 'Scan',
                        'route' => 'admin.file.permission.scan',
                    ],
                    [
                        'title' => 'Relink Storage',
                        'route' => 'admin.file.permission.relink.storage',
                    ],
                ],
            ],
        ],
    ],

];