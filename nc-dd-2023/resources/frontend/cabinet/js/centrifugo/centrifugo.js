import { Centrifuge } from 'centrifuge';
import WS_API from '@/api/v1/user/websocket';
import notification from '@/centrifugo/channels/notification';

/**
 * Инициализация подключения к centrifugo
 *
 * @return {Promise<void>}
 */
const initialize = async () => {
    try {
        const { baseUrl, channels, token } = await WS_API.getConfig();

        const centrifugo = new Centrifuge(baseUrl, { token });

        // Run subscriptions
        notification(centrifugo, channels.notification);

        centrifugo.connect();
    } catch (error) {
        throw error;
    }
};

export default {
    initialize
}

